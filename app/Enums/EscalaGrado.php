<?php

namespace App\Enums;

/**
 * Escala de grados (cinturones). Hay tres sistemas distintos:
 *
 * - Tigers: sistema propio (cada color liso y luego con animal).
 * - ForKids: intercala un cinturón "recomendado" antes de cada "decidido".
 * - Adultos (Jóvenes y Adultos): solo el "decidido" de cada color.
 *
 * ForKids y Adultos comparten los grados negros (1º a 9º Dan) tras Rojo/Negro.
 */
enum EscalaGrado: string
{
    case Tigers = 'tigers';
    case ForKids = 'for_kids';
    case Adultos = 'adultos';

    /**
     * Etiqueta legible en español.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Tigers => 'Tigers',
            self::ForKids => 'For Kids',
            self::Adultos => 'Jóvenes y Adultos',
        };
    }

    /**
     * Escala de grados que corresponde a un grupo etario.
     */
    public static function paraGrupo(GrupoEtario $grupo): self
    {
        return match ($grupo) {
            GrupoEtario::Tigers => self::Tigers,
            GrupoEtario::ForKids => self::ForKids,
            GrupoEtario::JovenesAdultos => self::Adultos,
        };
    }
}
