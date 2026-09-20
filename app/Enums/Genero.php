<?php

namespace App\Enums;

/**
 * Género del alumno (según el formulario de inscripción).
 */
enum Genero: string
{
    case Masculino = 'masculino';
    case Femenino = 'femenino';

    /**
     * Etiqueta legible en español.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Masculino => 'Masculino',
            self::Femenino => 'Femenino',
        };
    }
}
