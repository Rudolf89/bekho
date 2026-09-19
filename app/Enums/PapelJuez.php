<?php

namespace App\Enums;

/**
 * Papel del juez en una prueba de competencia. El juez central puede penalizar
 * con 0; los laterales (A y B) evalúan sus criterios.
 */
enum PapelJuez: string
{
    case A = 'a';
    case Central = 'central';
    case B = 'b';

    public function etiqueta(): string
    {
        return match ($this) {
            self::A => 'Juez A',
            self::Central => 'Juez central',
            self::B => 'Juez B',
        };
    }
}
