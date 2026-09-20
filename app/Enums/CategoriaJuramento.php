<?php

namespace App\Enums;

/**
 * Categoría de clase a la que aplica un juramento. Los juramentos ATA distinguen
 * entre Tigers y el resto (Kids y Adultos comparten juramento), agrupación que el
 * enum GrupoEtario no expresa (separa For Kids de Jóvenes y Adultos), por lo que
 * se usa un enum propio.
 */
enum CategoriaJuramento: string
{
    case Tigers = 'tigers';
    case KidsAdultos = 'kids_adultos';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Tigers => 'Tigers',
            self::KidsAdultos => 'Kids y Adultos',
        };
    }

    /**
     * Categoría de juramento que corresponde a un grupo etario.
     */
    public static function paraGrupo(GrupoEtario $grupo): self
    {
        return $grupo === GrupoEtario::Tigers ? self::Tigers : self::KidsAdultos;
    }
}
