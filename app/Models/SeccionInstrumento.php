<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sección de un instrumento de evaluación (p. ej. "Sparring"). Agrupa criterios.
 */
class SeccionInstrumento extends Model
{
    protected $table = 'secciones_instrumento';

    /**
     * @var list<string>
     */
    protected $fillable = ['instrumento_evaluacion_id', 'nombre', 'ponderacion', 'orden'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['ponderacion' => 'decimal:2'];
    }

    /**
     * @return BelongsTo<InstrumentoEvaluacion, $this>
     */
    public function instrumento(): BelongsTo
    {
        return $this->belongsTo(InstrumentoEvaluacion::class, 'instrumento_evaluacion_id');
    }

    /**
     * @return HasMany<CriterioInstrumento, $this>
     */
    public function criterios(): HasMany
    {
        return $this->hasMany(CriterioInstrumento::class, 'seccion_instrumento_id')->orderBy('orden');
    }
}
