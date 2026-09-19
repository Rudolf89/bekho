<?php

namespace App\Models;

use App\Enums\EstadoPlanillaCompetencia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Planilla operativa de competencia (prueba de certificación de planillero).
 * Transversal a la federación: competidores y jueces son ficticios, sin vínculo
 * con personas. No lleva grupo_id.
 */
class PlanillaCompetencia extends Model
{
    /**
     * @var string
     */
    protected $table = 'planillas_competencia';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'prueba_id',
        'grupo_edad_id',
        'categoria_competencia_id',
        'fecha',
        'nro_pista',
        'hora_inicio',
        'hora_termino',
        'genero',
        'estado',
        'observaciones',
        'creado_por_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'estado' => EstadoPlanillaCompetencia::class,
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
     * @return BelongsTo<GrupoEdad, $this>
     */
    public function grupoEdad(): BelongsTo
    {
        return $this->belongsTo(GrupoEdad::class, 'grupo_edad_id');
    }

    /**
     * @return BelongsTo<CategoriaCompetencia, $this>
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaCompetencia::class, 'categoria_competencia_id');
    }

    /**
     * @return HasMany<JuezPlanilla, $this>
     */
    public function jueces(): HasMany
    {
        return $this->hasMany(JuezPlanilla::class, 'planilla_id');
    }

    /**
     * @return HasMany<CompetidorPlanilla, $this>
     */
    public function competidores(): HasMany
    {
        return $this->hasMany(CompetidorPlanilla::class, 'planilla_id')->orderBy('orden');
    }
}
