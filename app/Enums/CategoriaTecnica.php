<?php

namespace App\Enums;

/**
 * Categoría de una técnica de la biblioteca (currículo ATA). Unifica el currículo
 * técnico disperso en los manuales (patadas, formas, manos, tricks, armas, etc.).
 */
enum CategoriaTecnica: string
{
    case Patada = 'patada';
    case Forma = 'forma';
    case Mano = 'mano';
    case Trick = 'trick';
    case Arma = 'arma';
    case Rompimiento = 'rompimiento';
    case Protech = 'protech';

    /**
     * Etiqueta legible en español.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Patada => 'Patadas',
            self::Forma => 'Formas',
            self::Mano => 'Manos',
            self::Trick => 'Tricks',
            self::Arma => 'Armas',
            self::Rompimiento => 'Rompimientos',
            self::Protech => 'Protech',
        };
    }
}
