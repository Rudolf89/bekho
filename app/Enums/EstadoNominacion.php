<?php

namespace App\Enums;

/**
 * Estado de una nominación a examen (grados que requieren nominación:
 * rojo-negro y danes).
 */
enum EstadoNominacion: string
{
    case Pendiente = 'pendiente';
    case Aprobada = 'aprobada';
    case Rechazada = 'rechazada';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Aprobada => 'Aprobada',
            self::Rechazada => 'Rechazada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pendiente => 'amber',
            self::Aprobada => 'green',
            self::Rechazada => 'zinc',
        };
    }
}
