<?php

namespace App\Enums;

/**
 * Tipo de un pago registrado.
 */
enum TipoPago: string
{
    case Mensualidad = 'mensualidad';
    case Matricula = 'matricula';

    /**
     * Etiqueta legible en español.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Mensualidad => 'Mensualidad',
            self::Matricula => 'Matrícula',
        };
    }
}
