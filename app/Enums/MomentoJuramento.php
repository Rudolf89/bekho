<?php

namespace App\Enums;

/**
 * Momento de la clase en que se recita un juramento: al inicio, al cierre o en
 * ambos.
 */
enum MomentoJuramento: string
{
    case Inicio = 'inicio';
    case Cierre = 'cierre';
    case Ambos = 'ambos';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Inicio => 'Inicio',
            self::Cierre => 'Cierre',
            self::Ambos => 'Inicio y cierre',
        };
    }

    /**
     * ¿Este momento aplica al inicio de la clase? (inicio o ambos)
     */
    public function esInicio(): bool
    {
        return $this === self::Inicio || $this === self::Ambos;
    }

    /**
     * ¿Este momento aplica al cierre de la clase? (cierre o ambos)
     */
    public function esCierre(): bool
    {
        return $this === self::Cierre || $this === self::Ambos;
    }
}
