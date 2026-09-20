<?php

namespace App\Enums;

/**
 * Tipo de un requisito de etapa de programa.
 *
 * - manual: se verifica a mano (papeleo, membresía, aranceles, plan de estudios
 *   demostrado, seminarios presenciales…). El instructor/licenciatario lo marca.
 * - cuestionario: se cumple solo cuando la persona tiene un intento APROBADO del
 *   cuestionario enlazado (prueba escrita).
 */
enum TipoRequisitoEtapa: string
{
    case Manual = 'manual';
    case Cuestionario = 'cuestionario';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Manual => 'Verificación manual',
            self::Cuestionario => 'Prueba escrita (cuestionario)',
        };
    }
}
