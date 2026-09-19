<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Escala de puntaje de la federación (competencia, rúbrica de evaluación).
 */
class EscalaPuntaje extends Model
{
    /**
     * @var string
     */
    protected $table = 'escalas_puntaje';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'federacion_id',
        'nombre',
        'minimo',
        'maximo',
        'paso',
        'activo',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'minimo' => 'decimal:2',
            'maximo' => 'decimal:2',
            'paso' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Federacion, $this>
     */
    public function federacion(): BelongsTo
    {
        return $this->belongsTo(Federacion::class);
    }
}
