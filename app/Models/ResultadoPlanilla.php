<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Resultado (lugar) de un competidor en una planilla de competencia.
 */
class ResultadoPlanilla extends Model
{
    /**
     * @var string
     */
    protected $table = 'resultados_planilla';

    /**
     * @var list<string>
     */
    protected $fillable = ['planilla_id', 'lugar', 'competidor_planilla_id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['lugar' => 'integer'];
    }

    /**
     * @return BelongsTo<PlanillaCompetencia, $this>
     */
    public function planilla(): BelongsTo
    {
        return $this->belongsTo(PlanillaCompetencia::class, 'planilla_id');
    }

    /**
     * @return BelongsTo<CompetidorPlanilla, $this>
     */
    public function competidor(): BelongsTo
    {
        return $this->belongsTo(CompetidorPlanilla::class, 'competidor_planilla_id');
    }
}
