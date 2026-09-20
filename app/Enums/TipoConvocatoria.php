<?php

namespace App\Enums;

/**
 * Tipo de convocatoria de examen. En las de tipo instructor se rinde con nota
 * (escala 9.1–9.9, mínimo de aprobación 9.5); las de la federación (danes,
 * nominaciones) las resuelve un panel y no llevan esa nota.
 */
enum TipoConvocatoria: string
{
    case Instructor = 'instructor';
    case Federacion = 'federacion';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Instructor => 'De instructor (con nota)',
            self::Federacion => 'De la federación',
        };
    }

    /**
     * ¿Se rinde con nota en esta convocatoria?
     */
    public function usaNota(): bool
    {
        return $this === self::Instructor;
    }
}
