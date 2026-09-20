<?php

namespace App\Enums;

/**
 * Clasifica una fila de atributos_tecnicos: los 10 atributos de la rúbrica con
 * que se evalúan formas y patadas, o los 3 criterios previos de conocimiento que
 * se agregan solo a las formas.
 */
enum TipoAtributoTecnico: string
{
    case Atributo = 'atributo';
    case CriterioForma = 'criterio_forma';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Atributo => 'Atributo técnico',
            self::CriterioForma => 'Criterio de conocimiento de forma',
        };
    }
}
