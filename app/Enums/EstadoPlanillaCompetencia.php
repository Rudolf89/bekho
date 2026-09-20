<?php

namespace App\Enums;

/**
 * Estado de una planilla de competencia (prueba de certificación de planillero).
 */
enum EstadoPlanillaCompetencia: string
{
    case Borrador = 'borrador';
    case Cerrada = 'cerrada';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::Cerrada => 'Cerrada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Borrador => 'amber',
            self::Cerrada => 'green',
        };
    }
}
