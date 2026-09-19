<?php

namespace App\Enums;

/**
 * Resultado de buscar una persona por documento (alta por documento / traslado).
 * Determina qué ve el grupo que consulta.
 */
enum ResultadoBusqueda: string
{
    case NoExiste = 'no_existe';
    case ExisteSinMatricula = 'existe_sin_matricula';
    case ExisteConMatricula = 'existe_con_matricula';
    case ExistePersonal = 'existe_personal';
    case Eliminada = 'eliminada';

    public function etiqueta(): string
    {
        return match ($this) {
            self::NoExiste => 'No existe',
            self::ExisteSinMatricula => 'Existe, sin matrícula activa',
            self::ExisteConMatricula => 'Existe con matrícula activa',
            self::ExistePersonal => 'Existe como personal de otro grupo',
            self::Eliminada => 'Eliminada (se puede restaurar)',
        };
    }
}
