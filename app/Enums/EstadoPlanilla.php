<?php

namespace App\Enums;

/**
 * Estado de una planilla imprimible del programa: la vigente es la que se usa
 * en la cancha; el borrador está en preparación y no debería repartirse.
 */
enum EstadoPlanilla: string
{
    case Vigente = 'vigente';
    case Borrador = 'borrador';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Vigente => 'Vigente',
            self::Borrador => 'Borrador',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Vigente => 'zinc',
            self::Borrador => 'red',
        };
    }
}
