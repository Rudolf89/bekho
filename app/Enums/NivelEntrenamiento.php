<?php

namespace App\Enums;

/**
 * Nivel de entrenamiento del alumno / de la clase.
 *
 * Es distinto del modelo Nivel del LMS (Fase 2): aquí describe el tramo de
 * avance del alumno en el tatami (Principiantes, Intermedio, Avanzado).
 */
enum NivelEntrenamiento: string
{
    case Principiantes = 'principiantes';
    case Intermedio = 'intermedio';
    case Avanzado = 'avanzado';

    /**
     * Etiqueta legible en español.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Principiantes => 'Principiantes',
            self::Intermedio => 'Intermedio',
            self::Avanzado => 'Avanzado',
        };
    }
}
