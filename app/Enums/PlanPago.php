<?php

namespace App\Enums;

/**
 * Plan de pago de la matrícula (reglamento del alumno): mensual (una mensualidad
 * por mes), semestral (6 meses por adelantado, con descuento de la sede) o anual
 * (12 meses por adelantado, con descuento de la sede). El plan define cuántos
 * meses cubre un cargo y qué descuento aplica.
 */
enum PlanPago: string
{
    case Mensual = 'mensual';
    case Semestral = 'semestral';
    case Anual = 'anual';

    /**
     * Meses que cubre un cargo de este plan.
     */
    public function meses(): int
    {
        return match ($this) {
            self::Mensual => 1,
            self::Semestral => 6,
            self::Anual => 12,
        };
    }

    public function etiqueta(): string
    {
        return match ($this) {
            self::Mensual => 'Mensual',
            self::Semestral => 'Semestral (6 meses)',
            self::Anual => 'Anual (12 meses)',
        };
    }
}
