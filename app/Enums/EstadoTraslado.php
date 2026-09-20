<?php

namespace App\Enums;

/**
 * Estado de una solicitud de traslado de un alumno entre grupos de la federación.
 */
enum EstadoTraslado: string
{
    case PendienteConsentimiento = 'pendiente_consentimiento'; // el grupo destino inició
    case PendienteAprobacion = 'pendiente_aprobacion';         // el titular/apoderado consintió
    case Bloqueada = 'bloqueada';                              // hay deuda en el grupo de origen
    case Completada = 'completada';                            // el origen aprobó y se ejecutó
    case Rechazada = 'rechazada';
    case Cancelada = 'cancelada';
    case Vencida = 'vencida';

    public function etiqueta(): string
    {
        return match ($this) {
            self::PendienteConsentimiento => 'Pendiente de consentimiento',
            self::PendienteAprobacion => 'Pendiente de aprobación',
            self::Bloqueada => 'Bloqueada por deuda',
            self::Completada => 'Completada',
            self::Rechazada => 'Rechazada',
            self::Cancelada => 'Cancelada',
            self::Vencida => 'Vencida',
        };
    }

    /**
     * Estados en los que la solicitud sigue "viva" (ocupa el cupo de una sola
     * solicitud pendiente por persona).
     *
     * @return array<int, string>
     */
    public static function pendientes(): array
    {
        return [
            self::PendienteConsentimiento->value,
            self::PendienteAprobacion->value,
            self::Bloqueada->value,
        ];
    }

    public function estaPendiente(): bool
    {
        return in_array($this->value, self::pendientes(), true);
    }
}
