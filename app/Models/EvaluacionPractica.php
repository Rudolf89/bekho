<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Evaluación práctica de una persona con un instrumento (historial transversal,
 * sin grupo_id). Guarda el puntaje/porcentaje obtenido y si aprobó.
 */
class EvaluacionPractica extends Model
{
    protected $table = 'evaluaciones_practicas';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'instrumento_evaluacion_id', 'persona_id', 'evaluador_persona_id',
        'fecha', 'puntaje_obtenido', 'porcentaje', 'aprobado', 'observaciones',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'puntaje_obtenido' => 'decimal:2',
            'porcentaje' => 'decimal:2',
            'aprobado' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<InstrumentoEvaluacion, $this>
     */
    public function instrumento(): BelongsTo
    {
        return $this->belongsTo(InstrumentoEvaluacion::class, 'instrumento_evaluacion_id');
    }

    /**
     * @return BelongsTo<Persona, $this>
     */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    /**
     * @return BelongsTo<Persona, $this>
     */
    public function evaluador(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'evaluador_persona_id');
    }

    /**
     * @return HasMany<PuntajeCriterio, $this>
     */
    public function puntajes(): HasMany
    {
        return $this->hasMany(PuntajeCriterio::class, 'evaluacion_practica_id');
    }
}
