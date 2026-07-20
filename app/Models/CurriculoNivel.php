<?php

namespace App\Models;

use App\Enums\NivelEntrenamiento;
use Illuminate\Database\Eloquent\Model;

/**
 * Currículo técnico de un nivel (fórmula, defensa, patadas, combinaciones y
 * roturas). Es el contenido que rellena los bloques de la planilla. Catálogo
 * compartido (sin academia_id).
 */
class CurriculoNivel extends Model
{
    protected $table = 'curriculos_nivel';

    /**
     * @var list<string>
     */
    protected $fillable = ['nivel', 'formula', 'defensa', 'patadas', 'combinaciones', 'roturas'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nivel' => NivelEntrenamiento::class,
            'combinaciones' => 'array',
        ];
    }
}
