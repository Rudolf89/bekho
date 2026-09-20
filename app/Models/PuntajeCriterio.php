<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Puntaje de un criterio dentro de una evaluación práctica.
 */
class PuntajeCriterio extends Model
{
    protected $table = 'puntajes_criterio';

    /**
     * @var list<string>
     */
    protected $fillable = ['evaluacion_practica_id', 'criterio_instrumento_id', 'puntaje'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['puntaje' => 'decimal:2'];
    }

    /**
     * @return BelongsTo<EvaluacionPractica, $this>
     */
    public function evaluacion(): BelongsTo
    {
        return $this->belongsTo(EvaluacionPractica::class, 'evaluacion_practica_id');
    }

    /**
     * @return BelongsTo<CriterioInstrumento, $this>
     */
    public function criterio(): BelongsTo
    {
        return $this->belongsTo(CriterioInstrumento::class, 'criterio_instrumento_id');
    }
}
