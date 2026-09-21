<?php

namespace App\Services;

use App\Enums\EstadoCargo;
use App\Enums\PlanPago;
use App\Models\Beca;
use App\Models\Cargo;
use App\Models\Matricula;
use App\Models\Sede;
use App\Models\TarifaSede;
use App\Models\TipoCargo;
use App\Models\Tutela;
use App\Support\Tenancy\Grupo;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
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
        // Operación de sistema: recorre las matrículas de TODOS los grupos y lee
        // sus relaciones (suspensiones, becas). El aislamiento se desactiva a
        // propósito para que esas lecturas no fallen cerradas sin tenant.
        return Grupo::comoSistema(function () use ($periodo): int {
            $periodo = $this->periodo($periodo);
            $creados = 0;

            foreach (Matricula::withoutGlobalScopes()->activas()->get() as $matricula) {
                // Solo el plan mensual se cobra mes a mes; semestral y anual se cobran
                // por adelantado en un único cargo (ver generarCargoPlan).
                if (($matricula->plan_pago ?? PlanPago::Mensual) !== PlanPago::Mensual) {
                    continue;
                }

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
        });
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
     * Descuento (%) del plan según la sede: mensual no tiene descuento; semestral y
     * anual usan el porcentaje configurado en la sede (0 hasta que se configure).
     */
    public function descuentoPlan(Sede $sede, PlanPago $plan): int
    {
        return match ($plan) {
            PlanPago::Mensual => 0,
            PlanPago::Semestral => $sede->descuento_semestral_pct ?? 0,
            PlanPago::Anual => $sede->descuento_anual_pct ?? 0,
        };
    }

    /**
     * Monto de un cargo de plan (semestral/anual): mensualidad de la sede (con tramo
     * familiar y beca) × meses del plan, menos el descuento del plan que fija la sede.
     * Falla explícita igual que calcularMensualidad si no hay sede o tarifa.
     *
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function montoPlan(Matricula $matricula, TipoCargo $tipo, CarbonInterface $periodo): array
    {
        $plan = $matricula->plan_pago ?? PlanPago::Mensual;
        [$mensual, $detalle] = $this->calcularMensualidad($matricula, $tipo, $periodo);

        $meses = $plan->meses();
        $sede = Sede::withoutGlobalScopes()->findOrFail($matricula->sede_id);
        $descuento = $this->descuentoPlan($sede, $plan);
        $subtotal = $mensual * $meses;
        $monto = (int) round($subtotal * (100 - $descuento) / 100);

        return [$monto, array_merge($detalle, [
            'plan' => $plan->value,
            'meses' => $meses,
            'mensualidad' => $mensual,
            'descuento_plan_pct' => $descuento,
            'monto_final' => $monto,
        ])];
    }

    /**
     * Genera (idempotente) el cargo por adelantado de un plan semestral o anual para
     * la matrícula. Best-effort: devuelve null si el plan es mensual, si la matrícula
     * no tiene sede o si la sede no tiene la tarifa de mensualidad (no bloquea la
     * inscripción). Idempotente por (matrícula, tipo mensualidad, período).
     */
    public function generarCargoPlan(Matricula $matricula, ?CarbonInterface $periodo = null): ?Cargo
    {
        $plan = $matricula->plan_pago ?? PlanPago::Mensual;
        if ($plan === PlanPago::Mensual || ! $matricula->sede_id) {
            return null;
        }

        $tipo = $this->tipoMensualidad($matricula->grupo->federacion_id ?? null);
        if (! $tipo) {
            return null;
        }

        $familia = $this->tamanoFamilia($matricula);
        if (! $this->tarifaPara($matricula->sede_id, $tipo->id, $familia)) {
            return null;
        }

        $periodo = $this->periodo($periodo);

        $existe = Cargo::withoutGlobalScopes()
            ->where('matricula_id', $matricula->id)
            ->where('tipo_cargo_id', $tipo->id)
            ->whereDate('periodo', $periodo)
            ->where('estado', '!=', EstadoCargo::Anulado->value)
            ->exists();

        if ($existe) {
            return null;
        }

        [$monto, $detalle] = $this->montoPlan($matricula, $tipo, $periodo);

        return Cargo::withoutGlobalScopes()->create([
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
     * Genera un cargo único (no recurrente) para la matrícula — p. ej. matrícula
     * de ingreso o uniforme — con la tarifa de la sede (tramo de 1). Best-effort:
     * devuelve null si la matrícula no tiene sede o la sede no tiene esa tarifa
     * (no bloquea la inscripción). Idempotente por (matrícula, tipo, período).
     */
    public function generarCargoUnico(Matricula $matricula, TipoCargo $tipo, ?CarbonInterface $vence = null, ?CarbonInterface $periodo = null): ?Cargo
    {
        if (! $matricula->sede_id) {
            return null;
        }

        $tarifa = $this->tarifaPara($matricula->sede_id, $tipo->id, 1);
        if (! $tarifa) {
            return null;
        }

        return Cargo::withoutGlobalScopes()->updateOrCreate(
            ['matricula_id' => $matricula->id, 'tipo_cargo_id' => $tipo->id, 'periodo' => $periodo?->toDateString()],
            [
                'grupo_id' => $matricula->grupo_id,
                'sede_id' => $matricula->sede_id,
                'monto' => $tarifa->monto_por_alumno,
                'vence_el' => ($vence ?? now())->toDateString(),
                'estado' => EstadoCargo::Pendiente->value,
                'detalle_calculo' => ['sede_id' => $matricula->sede_id, 'tramo' => 1, 'monto_base' => $tarifa->monto_por_alumno, 'monto_final' => $tarifa->monto_por_alumno],
            ],
        );
    }

    /**
     * Tipo de cargo "Matrícula" de la federación (una vez al año, feb–mar). Se
     * busca por el código estable 'matricula'; el nombre es solo un respaldo por
     * si el catálogo aún no tiene código.
     */
    public function tipoMatricula(?int $federacionId = null): ?TipoCargo
    {
        return $this->tipoPorCodigo('matricula', $federacionId)
            ?? TipoCargo::query()
                ->when($federacionId, fn ($q) => $q->where('federacion_id', $federacionId))
                ->where('nombre', 'Matrícula')
                ->first();
    }

    /**
     * ¿La matrícula está EXENTA de la matrícula anual del año dado? El reglamento:
     * el alumno nuevo que ingresó entre octubre (año anterior) y enero queda eximido
     * del pago de matrícula del período anual siguiente.
     */
    public function exentaDeMatricula(Matricula $matricula, int $anio): bool
    {
        $ingreso = $matricula->fecha_ingreso;

        if (! $ingreso) {
            return false;
        }

        $sede = $matricula->sede_id ? Sede::withoutGlobalScopes()->find($matricula->sede_id) : null;
        $desdeMes = $sede
            ? $sede->exencionMatriculaDesdeMes()
            : ($matricula->grupo->federacion->exencion_matricula_desde_mes ?? 10);
        $hastaMes = $sede
            ? $sede->exencionMatriculaHastaMes()
            : ($matricula->grupo->federacion->exencion_matricula_hasta_mes ?? 1);

        // La ventana cruza el fin de año: desde_mes del año anterior hasta hasta_mes
        // del año objetivo (por defecto, octubre del año previo → fin de enero).
        $desde = Carbon::create($anio - 1, $desdeMes, 1)->startOfDay();
        $hasta = Carbon::create($anio, $hastaMes, 1)->endOfMonth()->endOfDay();

        return $ingreso->betweenIncluded($desde, $hasta);
    }

    /**
     * Genera (idempotente) la matrícula ANUAL del año dado para las matrículas
     * activas, salvo las exentas por haber ingresado en octubre–enero. El monto
     * sale de la tarifa de la sede; sin tarifa se omite. Devuelve cuántas creó.
     */
    public function generarMatriculasAnuales(?int $anio = null): int
    {
        // Operación de sistema: recorre las matrículas de TODOS los grupos.
        return Grupo::comoSistema(function () use ($anio): int {
            $anio = $anio ?? (int) now()->year;
            $periodo = Carbon::create($anio, 1, 1)->startOfDay();
            $creadas = 0;

            foreach (Matricula::withoutGlobalScopes()->activas()->get() as $matricula) {
                if ($this->exentaDeMatricula($matricula, $anio)) {
                    continue;
                }

                $tipo = $this->tipoMatricula($matricula->grupo->federacion_id ?? null);
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

                // Vence en marzo (fin del período feb–mar de matrícula).
                $vence = Carbon::create($anio, 3, 31);
                if ($this->generarCargoUnico($matricula, $tipo, $vence, $periodo)) {
                    $creadas++;
                }
            }

            return $creadas;
        });
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
        return $this->tipoPorCodigo('mensualidad', $federacionId)
            ?? TipoCargo::query()
                ->when($federacionId, fn ($q) => $q->where('federacion_id', $federacionId))
                ->where('recurrente', true)
                ->orderBy('orden')
                ->first();
    }

    /**
     * Tipo de cargo por su código estable dentro de la federación (si se indica).
     */
    private function tipoPorCodigo(string $codigo, ?int $federacionId): ?TipoCargo
    {
        return TipoCargo::query()
            ->when($federacionId, fn ($q) => $q->where('federacion_id', $federacionId))
            ->where('codigo', $codigo)
            ->first();
    }

    private function venceEl(Matricula $matricula, CarbonInterface $periodo): CarbonInterface
    {
        $dia = $matricula->dia_vencimiento ?: 5;

        return $periodo->copy()->day(min($dia, $periodo->daysInMonth));
    }
}
