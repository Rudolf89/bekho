<?php

namespace App\Enums;

/**
 * Tipo de sede. No todas las sedes son iguales: algunas están abiertas al
 * público (grupo) y otras funcionan dentro de una entidad (club, colegio,
 * jardín), que puede ser privada (solo para quienes pertenecen a ella).
 */
enum TipoSede: string
{
    case Grupo = 'grupo';
    case Club = 'club';
    case Colegio = 'colegio';
    case Jardin = 'jardin';

    /**
     * Etiqueta legible en español.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Grupo => 'Grupo',
            self::Club => 'Club',
            self::Colegio => 'Colegio',
            self::Jardin => 'Jardín',
        };
    }
}
