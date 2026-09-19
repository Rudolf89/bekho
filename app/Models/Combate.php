<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Combate de una planilla de competencia entre dos competidores (o libre, si
 * competidor_b es nulo).
 */
class Combate extends Model
{
    /**
     * @var string
     */
    protected $table = 'combates';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'planilla_id',
        'ronda',
        'orden',
        'competidor_a_id',
        'competidor_b_id',
        'ganador_id',
        'tipo',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['orden' => 'integer'];
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
    public function competidorA(): BelongsTo
    {
        return $this->belongsTo(CompetidorPlanilla::class, 'competidor_a_id');
    }

    /**
     * @return BelongsTo<CompetidorPlanilla, $this>
     */
    public function competidorB(): BelongsTo
    {
        return $this->belongsTo(CompetidorPlanilla::class, 'competidor_b_id');
    }

    /**
     * @return BelongsTo<CompetidorPlanilla, $this>
     */
    public function ganador(): BelongsTo
    {
        return $this->belongsTo(CompetidorPlanilla::class, 'ganador_id');
    }

    /**
     * @return HasMany<MarcaCombate, $this>
     */
    public function marcas(): HasMany
    {
        return $this->hasMany(MarcaCombate::class, 'combate_id');
    }
}
