<?php

namespace App\Models;

use App\Enums\PapelJuezPlanilla;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Juez (ficticio) de una planilla de competencia.
 */
class JuezPlanilla extends Model
{
    /**
     * @var string
     */
    protected $table = 'jueces_planilla';

    /**
     * @var list<string>
     */
    protected $fillable = ['planilla_id', 'papel', 'nombre', 'nivel_pais', 'firmado_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'papel' => PapelJuezPlanilla::class,
            'firmado_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PlanillaCompetencia, $this>
     */
    public function planilla(): BelongsTo
    {
        return $this->belongsTo(PlanillaCompetencia::class, 'planilla_id');
    }
}
