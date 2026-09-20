<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Marca de un competidor en un combate: puntos, advertencias (total, sin separar
 * contacto de no contacto) y descalificación (la marca el planillero).
 */
class MarcaCombate extends Model
{
    /**
     * @var string
     */
    protected $table = 'marcas_combate';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'combate_id',
        'competidor_planilla_id',
        'puntos',
        'advertencias',
        'descalificado',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'puntos' => 'integer',
            'advertencias' => 'integer',
            'descalificado' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Combate, $this>
     */
    public function combate(): BelongsTo
    {
        return $this->belongsTo(Combate::class, 'combate_id');
    }

    /**
     * @return BelongsTo<CompetidorPlanilla, $this>
     */
    public function competidor(): BelongsTo
    {
        return $this->belongsTo(CompetidorPlanilla::class, 'competidor_planilla_id');
    }
}
