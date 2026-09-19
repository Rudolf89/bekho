<?php

namespace App\Enums;

/**
 * Estado de un cargo (cobro generado a una matrícula).
 */
enum EstadoCargo: string
{
    case Pendiente = 'pendiente';
    case Pagado = 'pagado';
    case Anulado = 'anulado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Pagado => 'Pagado',
            self::Anulado => 'Anulado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pendiente => 'amber',
            self::Pagado => 'green',
            self::Anulado => 'zinc',
        };
    }
}
