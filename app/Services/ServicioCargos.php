<?php

namespace App\Services;

use App\Enums\EstadoCargo;
use App\Models\Beca;
use App\Models\Cargo;
use App\Models\Matricula;
use App\Models\TarifaSede;
use App\Models\TipoCargo;
use App\Models\Tutela;
use Carbon\CarbonInterface;
use RuntimeException;

/**
 * Generación y cálculo de cargos (cobros). Una matrícula activa genera cargos
 * (incluidos los meses de vacaciones); suspendida y retirada no. El cobro es POR
 * SEDE: el monto usa el tramo de tarifas_sede por tamaño de familia EN LA SEDE y
 * aplica la beca vigente. Sin tarifa en la sede no se genera cargo (falla
 * explícita, sin valor por defecto). El detalle del cálculo se congela en el cargo.
 */
class ServicioCargos
{
    public function periodo(?CarbonInterface $fecha = null): CarbonInterface
    {
        return ($fecha ?? now())->copy()->startOfMonth();
    }

    /**
     * Genera (idempotente) los cargos de mensualidad del período para todas las
     * matrículas activas y no suspendidas. Devuelve cuántos creó.
     */
    public function generarMensualidades(?CarbonInterface $periodo = null): int
    {
        $periodo = $this->periodo($periodo);
        $creados = 0;

        foreach (Matricula::withoutGlobalScopes()->activas()->get() as $matricula) {
            if ($matricula->estaSuspendidaEn($periodo)) {
                continue;
            }

            $tipo = $this->tipoMensualidad($matricula->grupo->federacion_id ?? null);
            if (! $tipo) {
                continue;
            }

            $existe = Cargo::withoutGlobalScopes()
                ->where('matricula_id', $matricula->id)
                ->where('tipo_cargo_id', $tipo->id)
                ->whereDate('periodo', $periodo)
                ->where('estado', '!=', EstadoCargo::Anulado->value)
                ->exists();

            if ($existe) {
                continue;
            }

            [$monto, $detalle] = $this->calcularMensualidad($matricula, $tipo, $periodo);

            Cargo::withoutGlobalScopes()->create([
                'grupo_id' => $matricula->grupo_id,
                'matricula_id' => $matricula->id,
                'sede_id' => $matricula->sede_id,
                'tipo_cargo_id' => $tipo->id,
                'periodo' => $periodo,
                'monto' => $monto,
                'vence_el' => $this->venceEl($matricula, $periodo),
                'estado' => EstadoCargo::Pendiente->value,
                'detalle_calculo' => $detalle,
            ]);
            $creados++;
        }

        return $creados;
    }

    /**
     * Calcula el monto de la mensualidad: tramo por tamaño de familia EN LA SEDE +
     * beca. Falla explícita si la matrícula no tiene sede o la sede no tiene tarifa
     * para el tipo de cargo (no hay valor por defecto ni respaldo del grupo).
     *
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function calcularMensualidad(Matricula $matricula, TipoCargo $tipo, CarbonInterface $periodo): array
    {
        if (! $matricula->sede_id) {
            throw new RuntimeException("La matrícula #{$matricula->id} no tiene sede: no se puede cobrar.");
        }

        $familia = $this->tamanoFamilia($matricula);
        $tarifa = $this->tarifaPara($matricula->sede_id, $tipo->id, $familia);

        if (! $tarifa) {
            throw new RuntimeException(
                "La sede #{$matricula->sede_id} no tiene tarifa de '{$tipo->nombre}' para {$familia} alumno(s): carga las tarifas de la sede."
            );
        }

        $base = $tarifa->monto_por_alumno;

        $beca = Beca::withoutGlobalScopes()
            ->where('matricula_id', $matricula->id)
            ->get()
            ->first(fn (Beca $b) => $b->estaVigente($periodo));

        $monto = $beca ? $beca->aplicar($base) : $base;

        return [$monto, [
            'sede_id' => $matricula->sede_id,
            'familia' => $familia,
            'tramo' => $tarifa->cantidad_alumnos,
            'monto_base' => $base,
            'beca_id' => $beca?->id,
            'monto_final' => $monto,
        ]];
    }

    /**
     * Tamaño de la familia EN LA SEDE de la matrícula: matrículas activas de las
     * personas que comparten responsable de pago (tutelas) y están en la misma
     * sede. Los hermanos en otra sede NO suman. Mínimo 1 (la propia matrícula).
     */
    public function tamanoFamilia(Matricula $matricula): int
    {
        if (! $matricula->sede_id) {
            return 1;
        }

        $responsables = Tutela::query()
            ->where('alumno_persona_id', $matricula->persona_id)
            ->where('responsable_pago', true)
            ->pluck('apoderado_persona_id');

        if ($responsables->isEmpty()) {
            return 1;
        }

        $personaIds = Tutela::query()
            ->whereIn('apoderado_persona_id', $responsables)
            ->where('responsable_pago', true)
            ->pluck('alumno_persona_id')
            ->unique();

        $familia = Matricula::withoutGlobalScopes()->activas()
            ->where('sede_id', $matricula->sede_id)
            ->whereIn('persona_id', $personaIds)
            ->count();

        return max(1, $familia);
    }

    /**
     * Tarifa aplicable de la sede: el mayor tramo cuya cantidad_alumnos no supere
     * el tamaño de familia.
     */
    public function tarifaPara(int $sedeId, int $tipoCargoId, int $familia): ?TarifaSede
    {
        return TarifaSede::query()
            ->where('sede_id', $sedeId)
            ->where('tipo_cargo_id', $tipoCargoId)
            ->where('cantidad_alumnos', '<=', $familia)
            ->orderByDesc('cantidad_alumnos')
            ->first();
    }

    /**
     * ¿La matrícula tiene deuda (cargo pendiente con período ≤ el dado)?
     */
    public function tieneDeuda(Matricula $matricula, ?CarbonInterface $periodo = null): bool
    {
        $periodo = $this->periodo($periodo);

        return Cargo::withoutGlobalScopes()
            ->where('matricula_id', $matricula->id)
            ->pendientes()
            ->whereDate('periodo', '<=', $periodo)
            ->exists();
    }

    private function tipoMensualidad(?int $federacionId): ?TipoCargo
    {
        return TipoCargo::query()
            ->when($federacionId, fn ($q) => $q->where('federacion_id', $federacionId))
            ->where('recurrente', true)
            ->orderBy('orden')
            ->first();
    }

    private function venceEl(Matricula $matricula, CarbonInterface $periodo): CarbonInterface
    {
        $dia = $matricula->dia_vencimiento ?: 5;

        return $periodo->copy()->day(min($dia, $periodo->daysInMonth));
    }
}
