<?php

namespace App\Notifications;

use App\Enums\EstadoIntento;
use App\Models\IntentoCuestionario;
use Illuminate\Notifications\Notification;

/**
 * Notifica al alumno que el examinador decidió su intento de cuestionario
 * (aprobado o volver a intentar). Canal database (se ve en la app).
 */
class IntentoDecidido extends Notification
{
    public function __construct(private IntentoCuestionario $intento) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $estado = $this->intento->estado;

        return [
            'intento_id' => $this->intento->id,
            'cuestionario' => $this->intento->cuestionario?->titulo,
            'estado' => $estado->value,
            'estado_etiqueta' => $estado->etiqueta(),
            'color' => $estado->color(),
            'porcentaje' => $this->intento->porcentaje,
            'justificacion' => $this->intento->justificacion,
            'titulo' => $estado === EstadoIntento::Aprobado
                ? 'Tu intento fue aprobado'
                : 'Debes volver a intentar',
            'mensaje' => $estado === EstadoIntento::Aprobado
                ? 'El examinador aprobó tu intento de «'.$this->intento->cuestionario?->titulo.'».'
                : 'El examinador te pidió volver a intentar «'.$this->intento->cuestionario?->titulo.'».',
        ];
    }
}
