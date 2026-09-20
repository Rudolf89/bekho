<?php

namespace App\Enums;

/**
 * Estado de una inscripción en un nivel del Programa Legacy (track de
 * formación de instructores).
 */
enum EstadoLegacy: string
{
    case EnCurso = 'en_curso';   // acumulando horas y cumpliendo requisitos
    case Aprobado = 'aprobado';  // el licenciatario aprobó el ascenso de nivel

    public function etiqueta(): string
    {
        return match ($this) {
            self::EnCurso => 'En curso',
            self::Aprobado => 'Aprobado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::EnCurso => 'sky',
            self::Aprobado => 'green',
        };
    }
}
