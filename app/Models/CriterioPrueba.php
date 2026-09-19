<?php

namespace App\Models;

use App\Enums\PapelJuez;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Criterio de evaluación de una prueba, asignado a un papel de juez y una escala.
 */
class CriterioPrueba extends Model
{
    /**
     * @var string
     */
    protected $table = 'criterios_prueba';

    /**
     * @var list<string>
     */
    protected $fillable = ['prueba_id', 'papel_juez', 'nombre', 'escala_id', 'permite_cero', 'orden'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'papel_juez' => PapelJuez::class,
            'permite_cero' => 'boolean',
            'orden' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Prueba, $this>
     */
    public function prueba(): BelongsTo
    {
        return $this->belongsTo(Prueba::class);
    }

    /**
     * @return BelongsTo<EscalaPuntaje, $this>
     */
    public function escala(): BelongsTo
    {
        return $this->belongsTo(EscalaPuntaje::class, 'escala_id');
    }
}
