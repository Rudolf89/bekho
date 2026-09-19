<?php

namespace App\Enums;

/**
 * Parentesco del apoderado respecto del alumno en una tutela.
 */
enum Parentesco: string
{
    case Padre = 'padre';
    case Madre = 'madre';
    case Tutor = 'tutor';
    case Otro = 'otro';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Padre => 'Padre',
            self::Madre => 'Madre',
            self::Tutor => 'Tutor legal',
            self::Otro => 'Otro',
        };
    }
}
