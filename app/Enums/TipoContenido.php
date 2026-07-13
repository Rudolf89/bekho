<?php

namespace App\Enums;

/**
 * Tipo de un contenido de formación.
 */
enum TipoContenido: string
{
    case Texto = 'texto';
    case Video = 'video';
    case Documento = 'documento';

    /**
     * Etiqueta legible en español.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Texto => 'Texto',
            self::Video => 'Video',
            self::Documento => 'Documento',
        };
    }
}
