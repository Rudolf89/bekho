<?php

namespace App\Enums;

/**
 * Estado de una matrícula (vínculo alumno ↔ grupo). Una persona solo puede
 * tener UNA matrícula activa a la vez en toda la federación.
 */
enum EstadoMatricula: string
{
    case Activa = 'activa';
    case Suspendida = 'suspendida';
    case Retirada = 'retirada';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Activa => 'Activa',
            self::Suspendida => 'Suspendida',
            self::Retirada => 'Retirada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Activa => 'green',
            self::Suspendida => 'amber',
            self::Retirada => 'zinc',
        };
    }
}
