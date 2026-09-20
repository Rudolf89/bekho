<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Competidor (ficticio) de una planilla de competencia. Su total es la suma de
 * los dígitos de sus puntajes (puntaje en décimas menos 90; el 0 aporta 0).
 */
class CompetidorPlanilla extends Model
{
    /**
     * @var string
     */
    protected $table = 'competidores_planilla';

    /**
     * @var list<string>
     */
    protected $fillable = ['planilla_id', 'orden', 'nombre', 'edad', 'pais'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'edad' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<PlanillaCompetencia, $this>
     */
    public function planilla(): BelongsTo
    {
        return $this->belongsTo(PlanillaCompetencia::class, 'planilla_id');
    }

    /**
     * @return HasMany<PuntajePlanilla, $this>
     */
    public function puntajes(): HasMany
    {
        return $this->hasMany(PuntajePlanilla::class, 'competidor_planilla_id');
    }

    /**
     * Total del competidor: suma de los dígitos (puntaje − 90; el 0 aporta 0).
     */
    public function total(): int
    {
        return (int) $this->puntajes->sum(fn (PuntajePlanilla $p) => $p->digito());
    }
}
