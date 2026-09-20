<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Instrumento (rúbrica) con que se evalúa a la persona en la práctica. Catálogo
 * de la federación; puede acotarse a un programa o etapa. La aprobación se define
 * por umbral de porcentaje O por nota mínima, según la escala.
 */
class InstrumentoEvaluacion extends Model
{
    protected $table = 'instrumentos_evaluacion';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'federacion_id', 'programa_id', 'etapa_programa_id', 'nombre', 'escala_id',
        'puntaje_maximo', 'umbral_porcentaje', 'nota_minima', 'activo', 'orden', 'fuente', 'verificado',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'puntaje_maximo' => 'decimal:2',
            'umbral_porcentaje' => 'integer',
            'nota_minima' => 'decimal:2',
            'activo' => 'boolean',
            'verificado' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<EscalaPuntaje, $this>
     */
    public function escala(): BelongsTo
    {
        return $this->belongsTo(EscalaPuntaje::class, 'escala_id');
    }

    /**
     * @return BelongsTo<Programa, $this>
     */
    public function programa(): BelongsTo
    {
        return $this->belongsTo(Programa::class);
    }

    /**
     * @return HasMany<SeccionInstrumento, $this>
     */
    public function secciones(): HasMany
    {
        return $this->hasMany(SeccionInstrumento::class)->orderBy('orden');
    }

    /**
     * @return HasMany<EvaluacionPractica, $this>
     */
    public function evaluaciones(): HasMany
    {
        return $this->hasMany(EvaluacionPractica::class);
    }

    /**
     * @param  Builder<InstrumentoEvaluacion>  $query
     * @return Builder<InstrumentoEvaluacion>
     */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }
}
