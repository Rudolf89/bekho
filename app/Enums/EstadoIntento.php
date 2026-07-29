<?php

namespace App\Enums;

/**
 * Estado de la revisión de un intento de cuestionario. El auto-puntaje es
 * inmediato, pero la aprobación final la decide el examinador.
 */
enum EstadoIntento: string
{
    case Pendiente = 'pendiente';   // esperando decisión del examinador
    case Aprobado = 'aprobado';     // el examinador lo dio por aprobado
    case Reintentar = 'reintentar'; // el examinador pide volver a intentar

    public function etiqueta(): string
    {
        return match ($this) {
            self::Pendiente => 'En revisión',
            self::Aprobado => 'Aprobado',
            self::Reintentar => 'Debe reintentar',
        };
    }

    /**
     * Color del badge (paleta Flux).
     */
    public function color(): string
    {
        return match ($this) {
            self::Pendiente => 'amber',
            self::Aprobado => 'green',
            self::Reintentar => 'red',
        };
    }
}
