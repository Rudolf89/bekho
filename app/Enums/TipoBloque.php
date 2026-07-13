<?php

namespace App\Enums;

/**
 * Tipo de bloque de actividad de una planilla (línea de tiempo de la clase).
 *
 * "Combat Weapon" y "Sparring" van en inglés a propósito: así se llaman en la
 * escuela.
 */
enum TipoBloque: string
{
    case Calentamiento = 'calentamiento';
    case Formula = 'formula';
    case DefensaYAtaque = 'defensa_y_ataque';
    case Armas = 'armas';
    case Patadas = 'patadas';
    case Roturas = 'roturas';
    case CombatWeapon = 'combat_weapon';
    case Sparring = 'sparring';
    case AnunciosPremios = 'anuncios_premios';

    /**
     * Etiqueta legible en español.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Calentamiento => 'Calentamiento',
            self::Formula => 'Fórmula',
            self::DefensaYAtaque => 'Defensa y Ataque',
            self::Armas => 'Armas (SJB/BME)',
            self::Patadas => 'Patadas',
            self::Roturas => 'Roturas',
            self::CombatWeapon => 'Combat Weapon',
            self::Sparring => 'Sparring',
            self::AnunciosPremios => 'Anuncios/Premios',
        };
    }
}
