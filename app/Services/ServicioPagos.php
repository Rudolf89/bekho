<?php

namespace App\Services;

use App\Enums\EstadoMatricula;
use App\Enums\TipoPago;
use App\Models\ConfiguracionPago;
use App\Models\Matricula;
use App\Models\Pago;
use App\Models\Tutela;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Lógica de dominio de pagos y morosidad (por matrícula).
 *
 * Morosidad "solo se marca": una matrícula activa está morosa si no tiene
 * registrada la mensualidad del período vigente. No se bloquea ningún acceso.
 */
class ServicioPagos
{
    /**
     * Primer día del mes de un período (por defecto, el mes actual).
     */
    public function periodo(?CarbonInterface $fecha = null): CarbonInterface
    {
        return ($fecha ?? now())->copy()->startOfMonth();
    }

    /**
     * Indica si la matrícula tiene pagada la mensualidad de un período.
     */
    public function estaAlDia(Matricula $matricula, ?CarbonInterface $periodo = null): bool
    {
        return $matricula->pagos()
            ->where('tipo', TipoPago::Mensualidad->value)
            ->whereDate('periodo', $this->periodo($periodo))
            ->exists();
    }

    /**
     * Indica si la matrícula (activa) está morosa en el período dado.
     */
    public function estaMoroso(Matricula $matricula, ?CarbonInterface $periodo = null): bool
    {
        return $matricula->estado === EstadoMatricula::Activa
            && ! $this->estaAlDia($matricula, $periodo);
    }

    /**
     * Matrículas activas morosas del período (respeta el scope por grupo).
     *
     * @return Collection<int, Matricula>
     */
    public function morosos(?CarbonInterface $periodo = null)
    {
        $periodo = $this->periodo($periodo);

        return Matricula::activas()
            ->whereDoesntHave('pagos', function ($query) use ($periodo): void {
                $query->where('tipo', TipoPago::Mensualidad->value)
                    ->whereDate('periodo', $periodo);
            })
            ->with('persona')
            ->get()
            ->sortBy(fn (Matricula $m) => $m->persona?->nombreCompleto())
            ->values();
    }

    /**
     * Indica si la matrícula tiene hermanos en la escuela (la persona comparte
     * apoderado, vía tutela vigente, con otra persona que tiene matrícula activa).
     */
    public function tieneHermanos(Matricula $matricula): bool
    {
        $apoderadoIds = Tutela::query()
            ->where('alumno_persona_id', $matricula->persona_id)
            ->pluck('apoderado_persona_id');

        if ($apoderadoIds->isEmpty()) {
            return false;
        }

        $hermanoPersonaIds = Tutela::query()
            ->whereIn('apoderado_persona_id', $apoderadoIds)
            ->where('alumno_persona_id', '!=', $matricula->persona_id)
            ->pluck('alumno_persona_id');

        if ($hermanoPersonaIds->isEmpty()) {
            return false;
        }

        return Matricula::activas()
            ->whereIn('persona_id', $hermanoPersonaIds)
            ->exists();
    }

    /**
     * Monto de mensualidad esperado para la matrícula según la configuración de
     * su grupo, aplicando el descuento por hermanos si corresponde.
     * Devuelve null si el grupo aún no fijó el valor de la mensualidad.
     */
    public function montoMensualidadEsperado(Matricula $matricula, ConfiguracionPago $config): ?int
    {
        if ($config->valor_mensualidad === null) {
            return null;
        }

        $monto = $config->valor_mensualidad;

        if ($this->tieneHermanos($matricula)) {
            $monto = (int) round($monto * (100 - $config->descuento_hermanos_pct) / 100);
        }

        return $monto;
    }

    /**
     * Registra un pago de la matrícula (registro manual).
     */
    public function registrarPago(
        Matricula $matricula,
        TipoPago $tipo,
        int $monto,
        CarbonInterface $fechaPago,
        ?CarbonInterface $periodo = null,
        ?string $medio = null,
    ): Pago {
        return Pago::updateOrCreate(
            [
                'matricula_id' => $matricula->id,
                'tipo' => $tipo->value,
                'periodo' => $tipo === TipoPago::Mensualidad ? $this->periodo($periodo) : null,
            ],
            [
                'grupo_id' => $matricula->grupo_id,
                'monto' => $monto,
                'fecha_pago' => $fechaPago,
                'medio' => $medio,
                'registrado_por' => Auth::id(),
            ],
        );
    }
}
