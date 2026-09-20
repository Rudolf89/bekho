<?php

namespace App\Enums;

/**
 * Tipo de un programa ATA.
 *
 * - disciplina: disciplinas paralelas (p. ej. Xtreme, Defense).
 * - progresion: rutas de progresión (p. ej. Black Belt Club, Master Club).
 * - formacion: formación de instructores (p. ej. Leadership, Legacy); estos
 *   programas se conectan con el módulo de Formación (LMS).
 */
enum TipoPrograma: string
{
    case Disciplina = 'disciplina';
    case Progresion = 'progresion';
    case Formacion = 'formacion';

    /**
     * Etiqueta legible en español.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Disciplina => 'Disciplina',
            self::Progresion => 'Progresión',
            self::Formacion => 'Formación',
        };
    }
}
