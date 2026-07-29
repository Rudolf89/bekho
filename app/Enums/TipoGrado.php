<?php

namespace App\Enums;

/**
 * Tipo de un grado (cinturón) dentro de su escala:
 *
 * - Base: cinturón de color liso (o los animales de Tigers).
 * - Recomendado / Decidido: la doble instancia por color del programa For Kids
 *   (se examina más seguido; primero "recomendado", luego "decidido").
 * - Dan: grado negro (1º a 9º Dan).
 */
enum TipoGrado: string
{
    case Base = 'base';
    case Recomendado = 'recomendado';
    case Decidido = 'decidido';
    case Dan = 'dan';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Base => 'Base',
            self::Recomendado => 'Recomendado',
            self::Decidido => 'Decidido',
            self::Dan => 'Dan',
        };
    }

    /**
     * Deriva el tipo a partir del nombre del grado.
     */
    public static function desdeNombre(string $nombre): self
    {
        return match (true) {
            str_contains($nombre, 'Recomendado') => self::Recomendado,
            str_contains($nombre, 'Decidido') => self::Decidido,
            (bool) preg_match('/\bDan\b/u', $nombre) => self::Dan,
            default => self::Base,
        };
    }
}
