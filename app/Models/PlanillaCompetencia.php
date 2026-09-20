<?php

namespace App\Models;

use App\Enums\EstadoPlanillaCompetencia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'evaluacion_practica_id',
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
     * Evaluación práctica (certificación de planillero) que respalda la planilla.
     *
     * @return BelongsTo<EvaluacionPractica, $this>
     */
    public function evaluacionPractica(): BelongsTo
    {
        return $this->belongsTo(EvaluacionPractica::class, 'evaluacion_practica_id');
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

    /**
     * @return HasMany<Combate, $this>
     */
    public function combates(): HasMany
    {
        return $this->hasMany(Combate::class, 'planilla_id')->orderBy('orden');
    }

    /**
     * @return HasMany<ResultadoPlanilla, $this>
     */
    public function resultados(): HasMany
    {
        return $this->hasMany(ResultadoPlanilla::class, 'planilla_id')->orderBy('lugar');
    }

    /**
     * @return HasOne<RecuentoMedallas, $this>
     */
    public function recuentoMedallas(): HasOne
    {
        return $this->hasOne(RecuentoMedallas::class, 'planilla_id');
    }
}
