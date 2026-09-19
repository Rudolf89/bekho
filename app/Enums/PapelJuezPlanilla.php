<?php

namespace App\Enums;

/**
 * Papel de un juez en la planilla de competencia. Además de los tres jueces que
 * puntúan (central, A, B) está el planillero, que lleva la planilla y marca la
 * descalificación pero no puntúa.
 */
enum PapelJuezPlanilla: string
{
    case Central = 'central';
    case A = 'a';
    case B = 'b';
    case Planillero = 'planillero';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Central => 'Juez central',
            self::A => 'Juez A',
            self::B => 'Juez B',
            self::Planillero => 'Planillero',
        };
    }
}
