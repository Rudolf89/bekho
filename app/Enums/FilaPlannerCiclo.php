<?php

namespace App\Enums;

/**
 * Fila del class planner de un ciclo (grilla del manual ATA): las categorías que
 * se planifican para cada bloque de semanas (1&2, 3&4, 5&6, 7&8).
 */
enum FilaPlannerCiclo: string
{
    case Calentamiento = 'calentamiento';
    case Patadas = 'patadas';
    case Formas = 'formas';
    case Cuadrantes = 'cuadrantes';
    case Protech = 'protech';
    case DrillsParejas = 'drills_parejas';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Calentamiento => 'Warm-Up',
            self::Patadas => 'Patadas (Kicks)',
            self::Formas => 'Formas (Forms)',
            self::Cuadrantes => 'Cuadrantes (Quadrants)',
            self::Protech => 'Protech',
            self::DrillsParejas => 'Drills en pareja (sobre blancos)',
        };
    }

    /**
     * Bloques de semanas de un ciclo (en orden).
     *
     * @return list<string>
     */
    public static function bloques(): array
    {
        return ['1&2', '3&4', '5&6', '7&8'];
    }
}
