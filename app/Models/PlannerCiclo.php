<?php

namespace App\Models;

use App\Enums\FilaPlannerCiclo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Celda del class planner de un ciclo (fila × bloque de semanas). Catálogo
 * compartido (cuelga del ciclo).
 */
class PlannerCiclo extends Model
{
    protected $table = 'planner_ciclo';

    /**
     * @var list<string>
     */
    protected $fillable = ['ciclo_id', 'fila', 'bloque', 'contenido'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['fila' => FilaPlannerCiclo::class];
    }

    /**
     * @return BelongsTo<Ciclo, $this>
     */
    public function ciclo(): BelongsTo
    {
        return $this->belongsTo(Ciclo::class);
    }
}
