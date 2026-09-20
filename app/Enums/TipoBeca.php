<?php

namespace App\Enums;

/**
 * Tipo de beca aplicada a una matrícula: un porcentaje de descuento o un monto fijo.
 */
enum TipoBeca: string
{
    case Porcentaje = 'porcentaje';
    case Monto = 'monto';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Porcentaje => 'Porcentaje',
            self::Monto => 'Monto fijo',
        };
    }
}
