<?php

namespace App\Enums;

/**
 * Grupo etario del estudiante. Cada alumno pertenece a UNO solo.
 *
 * OJO: For Kids y Jóvenes y Adultos se solapan a los 12 años; a esa edad el
 * grupo lo decide el instructor. Por eso el grupo NO se calcula automáticamente
 * desde la fecha de nacimiento: `sugerirPorEdad()` es solo una ayuda para el
 * formulario y el campo siempre queda editable a mano.
 */
enum GrupoEtario: string
{
    case Tigers = 'tigers';
    case ForKids = 'for_kids';
    case JovenesAdultos = 'jovenes_adultos';

    /**
     * Etiqueta legible en español.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Tigers => 'Tigers',
            self::ForKids => 'For Kids',
            self::JovenesAdultos => 'Jóvenes y Adultos',
        };
    }

    /**
     * Rango de edad de referencia (texto de ayuda).
     */
    public function rangoEdad(): string
    {
        return match ($this) {
            self::Tigers => '3 a 6 años',
            self::ForKids => '7 a 12 años',
            self::JovenesAdultos => '12 años en adelante',
        };
    }

    /**
     * Sugiere un grupo etario según la edad. Es SOLO una ayuda para el
     * formulario; la asignación definitiva la hace el instructor (por el
     * solapamiento a los 12 años).
     */
    public static function sugerirPorEdad(int $edad): self
    {
        return match (true) {
            $edad <= 6 => self::Tigers,
            $edad <= 12 => self::ForKids,
            default => self::JovenesAdultos,
        };
    }
}
