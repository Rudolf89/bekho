<?php

namespace App\Enums;

/**
 * Estado del progreso de un usuario sobre un contenido.
 */
enum EstadoProgreso: string
{
    case Pendiente = 'pendiente';
    case Visto = 'visto';
    case Completado = 'completado';

    /**
     * Etiqueta legible en español.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Visto => 'Visto',
            self::Completado => 'Completado',
        };
    }
}
