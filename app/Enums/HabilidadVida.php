<?php

namespace App\Enums;

/**
 * Habilidad para la Vida del día/semana (una de las seis).
 */
enum HabilidadVida: string
{
    case Disciplina = 'disciplina';
    case Conviccion = 'conviccion';
    case Comunicacion = 'comunicacion';
    case Respeto = 'respeto';
    case Autoestima = 'autoestima';
    case Honestidad = 'honestidad';

    /**
     * Etiqueta legible en español.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Disciplina => 'Disciplina',
            self::Conviccion => 'Convicción',
            self::Comunicacion => 'Comunicación',
            self::Respeto => 'Respeto',
            self::Autoestima => 'Autoestima',
            self::Honestidad => 'Honestidad',
        };
    }
}
