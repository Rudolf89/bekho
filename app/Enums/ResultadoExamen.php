<?php

namespace App\Enums;

/**
 * Resultado de un examen de grado (incluye la mención).
 */
enum ResultadoExamen: string
{
    case Aprobado = 'aprobado';
    case AprobadoConDistincion = 'aprobado_con_distincion';
    case Reprobado = 'reprobado';

    /**
     * Etiqueta legible en español.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Aprobado => 'Aprobado',
            self::AprobadoConDistincion => 'Aprobado con distinción',
            self::Reprobado => 'Reprobado',
        };
    }

    /**
     * Indica si el resultado implica graduación (sube de grado).
     */
    public function esAprobado(): bool
    {
        return $this === self::Aprobado || $this === self::AprobadoConDistincion;
    }
}
