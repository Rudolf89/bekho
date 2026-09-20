<?php

namespace App\Enums;

/**
 * Modalidad de una técnica. El currículo tradicional Songahm convive con las
 * modalidades Creative y Xtreme (ATA MAX) y el Tricking.
 */
enum ModalidadTecnica: string
{
    case Tradicional = 'tradicional';
    case Creative = 'creative';
    case Xtreme = 'xtreme';
    case Tricking = 'tricking';

    /**
     * Etiqueta legible.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Tradicional => 'Tradicional',
            self::Creative => 'Creative',
            self::Xtreme => 'Xtreme',
            self::Tricking => 'Tricking',
        };
    }
}
