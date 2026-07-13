<?php

namespace App\Services;

use App\Enums\TipoPago;
use App\Models\ConfiguracionPago;
use App\Models\Estudiante;
use App\Models\Pago;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Lógica de dominio de pagos y morosidad.
 *
 * Morosidad "solo se marca": un alumno activo está moroso si no tiene registrada
 * la mensualidad del período vigente. No se bloquea ningún acceso.
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
     * Indica si el estudiante tiene pagada la mensualidad de un período.
     */
    public function estaAlDia(Estudiante $estudiante, ?CarbonInterface $periodo = null): bool
    {
        return $estudiante->pagos()
            ->where('tipo', TipoPago::Mensualidad->value)
            ->whereDate('periodo', $this->periodo($periodo))
            ->exists();
    }

    /**
     * Indica si el estudiante (activo) está moroso en el período dado.
     */
    public function estaMoroso(Estudiante $estudiante, ?CarbonInterface $periodo = null): bool
    {
        return $estudiante->activo && ! $this->estaAlDia($estudiante, $periodo);
    }

    /**
     * Estudiantes activos morosos del período (respeta el scope por academia).
     *
     * @return Collection<int, Estudiante>
     */
    public function morosos(?CarbonInterface $periodo = null)
    {
        $periodo = $this->periodo($periodo);

        return Estudiante::activos()
            ->whereDoesntHave('pagos', function ($query) use ($periodo): void {
                $query->where('tipo', TipoPago::Mensualidad->value)
                    ->whereDate('periodo', $periodo);
            })
            ->orderBy('nombre')
            ->get();
    }

    /**
     * Indica si el estudiante tiene hermanos en la escuela (comparte apoderado
     * con al menos otro estudiante).
     */
    public function tieneHermanos(Estudiante $estudiante): bool
    {
        $apoderadoIds = $estudiante->apoderados()->pluck('users.id');

        if ($apoderadoIds->isEmpty()) {
            return false;
        }

        return Estudiante::query()
            ->where('id', '!=', $estudiante->id)
            ->whereHas('apoderados', fn ($q) => $q->whereIn('users.id', $apoderadoIds))
            ->exists();
    }

    /**
     * Monto de mensualidad esperado para el estudiante según la configuración de
     * su academia, aplicando el descuento por hermanos si corresponde.
     * Devuelve null si la academia aún no fijó el valor de la mensualidad.
     */
    public function montoMensualidadEsperado(Estudiante $estudiante, ConfiguracionPago $config): ?int
    {
        if ($config->valor_mensualidad === null) {
            return null;
        }

        $monto = $config->valor_mensualidad;

        if ($this->tieneHermanos($estudiante)) {
            $monto = (int) round($monto * (100 - $config->descuento_hermanos_pct) / 100);
        }

        return $monto;
    }

    /**
     * Registra un pago del estudiante (registro manual).
     */
    public function registrarPago(
        Estudiante $estudiante,
        TipoPago $tipo,
        int $monto,
        CarbonInterface $fechaPago,
        ?CarbonInterface $periodo = null,
        ?string $medio = null,
    ): Pago {
        return Pago::updateOrCreate(
            [
                'estudiante_id' => $estudiante->id,
                'tipo' => $tipo->value,
                'periodo' => $tipo === TipoPago::Mensualidad ? $this->periodo($periodo) : null,
            ],
            [
                'academia_id' => $estudiante->academia_id,
                'monto' => $monto,
                'fecha_pago' => $fechaPago,
                'medio' => $medio,
                'registrado_por' => Auth::id(),
            ],
        );
    }
}
