<?php

namespace App\Enums;

/**
 * Estado de una asistencia. Se mantiene simple (Presente/Ausente) para agilizar
 * la toma de lista en el tatami desde el celular.
 */
enum EstadoAsistencia: string
{
    case Presente = 'presente';
    case Ausente = 'ausente';

    /**
     * Etiqueta legible en español.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Presente => 'Presente',
            self::Ausente => 'Ausente',
        };
    }
}
