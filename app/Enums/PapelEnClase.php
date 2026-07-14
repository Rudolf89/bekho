<?php

namespace App\Enums;

/**
 * Papel de un instructor dentro de una clase. Una clase puede tener varios
 * instructores: p. ej. un titular y un ayudante, o un profesor y un instructor.
 * Ser "ayudante" NO es un rol global, es un papel dentro de una clase puntual:
 * el mismo instructor puede ser titular en una clase y ayudante en otra.
 */
enum PapelEnClase: string
{
    case Titular = 'titular';
    case Asistente = 'asistente';
    case Ayudante = 'ayudante';

    /**
     * Etiqueta legible en español.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Titular => 'Titular',
            self::Asistente => 'Asistente',
            self::Ayudante => 'Ayudante',
        };
    }
}
