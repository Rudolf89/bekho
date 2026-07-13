<?php

namespace App\Enums;

/**
 * Escala de grados (cinturones). Tigers usa un sistema de rangos y parches
 * propio, distinto al del resto de los grupos.
 *
 * - estandar: For Kids y Jóvenes y Adultos.
 * - tigers: sistema propio de Tigers.
 */
enum EscalaGrado: string
{
    case Estandar = 'estandar';
    case Tigers = 'tigers';

    /**
     * Etiqueta legible en español.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Estandar => 'Estándar',
            self::Tigers => 'Tigers',
        };
    }

    /**
     * Escala de grados que corresponde a un grupo etario.
     */
    public static function paraGrupo(GrupoEtario $grupo): self
    {
        return $grupo === GrupoEtario::Tigers ? self::Tigers : self::Estandar;
    }
}
