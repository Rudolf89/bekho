<?php

namespace App\Services;

use App\Enums\EstadoMatricula;
use App\Enums\EstadoTraslado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Persona;
use App\Models\Sede;
use App\Models\SolicitudTraslado;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Flujo de traslado de un alumno entre grupos: el grupo destino inicia, el
 * titular/apoderado consiente, y el grupo de origen aprueba (o queda bloqueada
 * por deuda). Al aprobar se retira la matrícula de origen y se crea la de destino.
 *
 * Nota: la "deuda que bloquea" se apoya hoy en la morosidad (mensualidad impaga
 * del período vigente) vía ServicioPagos; se afinará con la generación de cargos
 * de la Fase 5.
 */
class ServicioTraslados
{
    public function __construct(private ServicioPagos $pagos) {}

    /**
     * El grupo destino inicia una solicitud de traslado. Falla si ya hay una
     * solicitud pendiente para la persona (índice parcial).
     */
    public function solicitar(
        Persona $persona,
        ?Matricula $matriculaOrigen,
        Grupo $grupoDestino,
        ?Sede $sedeDestino = null,
        ?User $solicitante = null,
        ?string $motivo = null,
    ): SolicitudTraslado {
        return SolicitudTraslado::create([
            'persona_id' => $persona->id,
            'matricula_origen_id' => $matriculaOrigen?->id,
            'grupo_destino_id' => $grupoDestino->id,
            'sede_destino_id' => $sedeDestino?->id,
            'estado' => EstadoTraslado::PendienteConsentimiento->value,
            'solicitada_por_user_id' => $solicitante?->id,
            'motivo' => $motivo,
            'plazo_desde' => now()->toDateString(),
        ]);
    }

    /**
     * El titular adulto o un apoderado consiente el traslado. Si el grupo de
     * origen tiene deuda, la solicitud queda bloqueada; si no, pasa a aprobación.
     */
    public function consentir(
        SolicitudTraslado $solicitud,
        Persona $consiente,
        string $medio = 'presencial',
        ?string $respaldo = null,
    ): SolicitudTraslado {
        if ($solicitud->estado !== EstadoTraslado::PendienteConsentimiento) {
            return $solicitud;
        }

        $solicitud->update([
            'consentido_por_persona_id' => $consiente->id,
            'consentimiento_at' => now(),
            'consentimiento_medio' => $medio,
            'consentimiento_respaldo' => $respaldo,
            'estado' => $this->hayDeudaEnOrigen($solicitud)
                ? EstadoTraslado::Bloqueada->value
                : EstadoTraslado::PendienteAprobacion->value,
        ]);

        return $solicitud;
    }

    /**
     * Reevalúa una solicitud bloqueada: si ya no hay deuda, pasa a aprobación.
     */
    public function reevaluarBloqueo(SolicitudTraslado $solicitud): SolicitudTraslado
    {
        if ($solicitud->estado === EstadoTraslado::Bloqueada && ! $this->hayDeudaEnOrigen($solicitud)) {
            $solicitud->update(['estado' => EstadoTraslado::PendienteAprobacion->value]);
        }

        return $solicitud;
    }

    /**
     * El grupo de origen aprueba: retira la matrícula de origen y crea la de
     * destino (con matricula_origen_id). Transacción con bloqueo sobre la persona.
     */
    public function aprobar(SolicitudTraslado $solicitud, ?Persona $resuelta = null): ?Matricula
    {
        if ($solicitud->estado !== EstadoTraslado::PendienteAprobacion) {
            return null;
        }

        return DB::transaction(function () use ($solicitud, $resuelta) {
            // Bloqueo sobre la persona para revalidar deuda y consentimiento.
            Persona::whereKey($solicitud->persona_id)->lockForUpdate()->first();

            if ($this->hayDeudaEnOrigen($solicitud)) {
                $solicitud->update(['estado' => EstadoTraslado::Bloqueada->value]);

                return null;
            }

            $origen = $solicitud->matriculaOrigen()->withoutGlobalScopes()->first();
            $grupoEtario = $origen->grupo_etario->value ?? 'jovenes_adultos';

            if ($origen) {
                $origen->update([
                    'estado' => EstadoMatricula::Retirada->value,
                    'fecha_retiro' => now()->toDateString(),
                    'motivo_baja' => 'traslado',
                ]);
            }

            $destino = Matricula::withoutGlobalScopes()->create([
                'persona_id' => $solicitud->persona_id,
                'grupo_id' => $solicitud->grupo_destino_id,
                'sede_id' => $solicitud->sede_destino_id,
                'grupo_etario' => $grupoEtario,
                'estado' => EstadoMatricula::Activa->value,
                'fecha_ingreso' => now()->toDateString(),
                'matricula_origen_id' => $origen?->id,
            ]);

            $solicitud->update([
                'estado' => EstadoTraslado::Completada->value,
                'resuelta_por_persona_id' => $resuelta?->id,
            ]);

            return $destino;
        });
    }

    /**
     * Rechaza o cancela la solicitud (según quién actúa; aquí solo fija el estado).
     */
    public function cerrar(SolicitudTraslado $solicitud, EstadoTraslado $estado): SolicitudTraslado
    {
        if ($solicitud->estado->estaPendiente()) {
            $solicitud->update(['estado' => $estado->value]);
        }

        return $solicitud;
    }

    /**
     * ¿El grupo de origen tiene deuda que bloquea el traslado? Proxy actual:
     * la matrícula de origen está morosa en el período vigente.
     */
    public function hayDeudaEnOrigen(SolicitudTraslado $solicitud): bool
    {
        $origen = $solicitud->matriculaOrigen()->withoutGlobalScopes()->first();

        return $origen !== null && $this->pagos->estaMoroso($origen);
    }
}
