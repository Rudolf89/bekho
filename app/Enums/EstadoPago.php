<?php

namespace App\Enums;

/**
 * Estado de un pago (abono con verificación).
 */
enum EstadoPago: string
{
    case PorVerificar = 'por_verificar';
    case Verificado = 'verificado';
    case Anulado = 'anulado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::PorVerificar => 'Por verificar',
            self::Verificado => 'Verificado',
            self::Anulado => 'Anulado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PorVerificar => 'amber',
            self::Verificado => 'green',
            self::Anulado => 'zinc',
        };
    }
}
