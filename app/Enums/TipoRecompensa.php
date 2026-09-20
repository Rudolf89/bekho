<?php

namespace App\Enums;

/**
 * Tipo de recompensa/gamificación. Un solo módulo cubre los tres sistemas de
 * los manuales ATA:
 *
 * - FranjaConocimiento: Knowledge Stripes de MAK (Karate for Kids).
 * - StarTag: estrellas Tigers por buenas acciones/logros (se acumulan).
 * - Coleccionable: seis coleccionables, uno por Habilidad para la Vida Songahm.
 */
enum TipoRecompensa: string
{
    case FranjaConocimiento = 'franja_conocimiento';
    case StarTag = 'star_tag';
    case Coleccionable = 'coleccionable';

    public function etiqueta(): string
    {
        return match ($this) {
            self::FranjaConocimiento => 'Franjas de Conocimiento',
            self::StarTag => 'Star Tag (Estrellas Tigre)',
            self::Coleccionable => 'Coleccionables (Habilidades de Vida)',
        };
    }

    public function descripcion(): string
    {
        return match ($this) {
            self::FranjaConocimiento => 'Franjas que el alumno gana al demostrar conocimiento (programa Karate for Kids).',
            self::StarTag => 'Estrellas Tigre por buenas acciones y logros; se acumulan.',
            self::Coleccionable => 'Un coleccionable por cada una de las seis Habilidades para la Vida.',
        };
    }
}
