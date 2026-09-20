<?php

namespace App\Models;

use App\Enums\HabilidadVida;
use App\Models\HabilidadVida as HabilidadVidaModelo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Ciclo del currículo ATA: uno por Habilidad para la Vida Songahm (6 en total),
 * de 8 semanas. Es la columna vertebral que organiza lecciones de vida y class
 * planners. Catálogo compartido (sin grupo_id).
 */
class Ciclo extends Model
{
    protected $table = 'ciclos';

    /**
     * @var list<string>
     */
    protected $fillable = ['habilidad_vida', 'habilidad_vida_id', 'nombre', 'orden', 'semanas'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'habilidad_vida' => HabilidadVida::class,
            'orden' => 'integer',
            'semanas' => 'integer',
        ];
    }

    /**
     * Habilidad para la vida del catálogo del manual (FK). La clave enum
     * `habilidad_vida` se conserva como clave natural histórica.
     *
     * @return BelongsTo<HabilidadVidaModelo, $this>
     */
    public function habilidadVida(): BelongsTo
    {
        return $this->belongsTo(HabilidadVidaModelo::class, 'habilidad_vida_id');
    }

    /**
     * Lecciones de vida de este ciclo, por semana.
     *
     * @return HasMany<LeccionVida, $this>
     */
    public function lecciones(): HasMany
    {
        return $this->hasMany(LeccionVida::class)->orderBy('semana');
    }

    /**
     * Celdas del class planner de este ciclo (fila × bloque de semanas).
     *
     * @return HasMany<PlannerCiclo, $this>
     */
    public function planner(): HasMany
    {
        return $this->hasMany(PlannerCiclo::class);
    }

    /**
     * @param  Builder<Ciclo>  $query
     * @return Builder<Ciclo>
     */
    public function scopeOrdenados(Builder $query): Builder
    {
        return $query->orderBy('orden');
    }
}
