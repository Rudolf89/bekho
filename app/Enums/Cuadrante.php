<?php

namespace App\Enums;

/**
 * Cuadrantes de Enseñanza (marco pedagógico ATA). Es una capa transversal que el
 * instructor aplica a toda la clase, como lista de verificación.
 */
enum Cuadrante: string
{
    case Estructura = 'estructura';
    case Emocion = 'emocion';
    case Conocimiento = 'conocimiento';
    case Legado = 'legado';

    /**
     * Etiqueta legible en español.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Estructura => 'Estructura',
            self::Emocion => 'Emoción',
            self::Conocimiento => 'Conocimiento',
            self::Legado => 'Legado',
        };
    }

    /**
     * Descripción breve del cuadrante.
     */
    public function descripcion(): string
    {
        return match ($this) {
            self::Estructura => 'Gestión de clase',
            self::Emocion => 'Energía y ánimo',
            self::Conocimiento => 'Atributos técnicos',
            self::Legado => 'Desarrollo del carácter',
        };
    }
}
