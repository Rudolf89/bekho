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

    /**
     * Descripción de la categoría.
     */
    public function descripcion(): string
    {
        return match ($this) {
            self::Patada => 'Técnicas de pierna del currículo, por cinturón y grado.',
            self::Forma => 'Formas Songahm (poomsae): la secuencia oficial de cada rango, paso a paso.',
            self::Mano => 'Técnicas de mano: golpes, bloqueos y combinaciones.',
            self::Trick => 'Acrobacias y patadas de exhibición (tricking / creative / xtreme).',
            self::Arma => 'Armas ATA (Jahng Bong, Ssahng Jeol Bong, Ssahng Nat, Gum Do…) y sus formas.',
            self::Rompimiento => 'Técnicas de rotura de tablas por grado.',
            self::Protech => 'Defensa personal Protech por niveles.',
        };
    }

    /**
     * Emoji representativo (para dar contexto visual en la biblioteca).
     */
    public function emoji(): string
    {
        return match ($this) {
            self::Patada => '🦵',
            self::Forma => '🥋',
            self::Mano => '👊',
            self::Trick => '🤸',
            self::Arma => '🥢',
            self::Rompimiento => '🪵',
            self::Protech => '🛡️',
        };
    }

    /**
     * ¿Se espera que las técnicas de esta categoría tengan secuencia (pasos)?
     */
    public function llevaSecuencia(): bool
    {
        return in_array($this, [self::Forma, self::Arma, self::Mano], true);
    }
}
