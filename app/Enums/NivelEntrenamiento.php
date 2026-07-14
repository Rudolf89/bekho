<?php

namespace App\Enums;

/**
 * Nivel de entrenamiento del alumno / de la clase.
 *
 * Es distinto del modelo Nivel del LMS (Fase 2): aquí describe el tramo de
 * avance del alumno en el tatami. El nivel del alumno se deriva de su cinturón
 * (ver App\Models\Grado::nivelEntrenamiento()).
 */
enum NivelEntrenamiento: string
{
    case Principiantes = 'principiantes';
    case Intermedio = 'intermedio';
    case Avanzado = 'avanzado';
    case RojoNegro = 'rojo_negro';
    case Danes = 'danes';

    /**
     * Etiqueta legible en español.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Principiantes => 'Principiantes',
            self::Intermedio => 'Intermedio',
            self::Avanzado => 'Avanzado',
            self::RojoNegro => 'Rojo/Negro',
            self::Danes => 'Cinturones negros (danes)',
        };
    }

    /**
     * Color del distintivo (badge) para la interfaz.
     */
    public function color(): string
    {
        return match ($this) {
            self::Principiantes => 'green',
            self::Intermedio => 'amber',
            self::Avanzado => 'orange',
            self::RojoNegro => 'red',
            self::Danes => 'zinc',
        };
    }
}
