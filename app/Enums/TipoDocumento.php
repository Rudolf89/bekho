<?php

namespace App\Enums;

/**
 * Tipo de documento de identidad de una persona. Se conservan ambos si un
 * extranjero (pasaporte) obtiene después un RUT chileno.
 */
enum TipoDocumento: string
{
    case Rut = 'rut';
    case Pasaporte = 'pasaporte';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Rut => 'RUT',
            self::Pasaporte => 'Pasaporte',
        };
    }
}
