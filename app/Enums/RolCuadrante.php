<?php

namespace App\Enums;

/**
 * A quién le corresponde un ítem de un Cuadrante de Enseñanza: al alumno o al
 * instructor (cada cuadrante define responsabilidades para ambos).
 */
enum RolCuadrante: string
{
    case Alumno = 'alumno';
    case Instructor = 'instructor';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Alumno => 'Alumno',
            self::Instructor => 'Instructor',
        };
    }
}
