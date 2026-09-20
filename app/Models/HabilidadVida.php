<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Habilidad para la vida del Manual ATA Legacy (las 6 del programa). Catálogo de
 * la federación (sin grupo_id). No confundir con el enum `App\Enums\HabilidadVida`,
 * que aún clasifica lecciones/recompensas; los ciclos apuntan a esta tabla por FK.
 */
class HabilidadVida extends Model
{
    /**
     * @var string
     */
    protected $table = 'habilidades_vida';

    /**
     * @var list<string>
     */
    protected $fillable = ['federacion_id', 'nombre', 'definicion', 'orden', 'fuente', 'verificado'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'verificado' => 'boolean',
        ];
    }

    /**
     * @param  Builder<HabilidadVida>  $query
     * @return Builder<HabilidadVida>
     */
    public function scopeOrdenadas(Builder $query): Builder
    {
        return $query->orderBy('orden');
    }

    /**
     * @return BelongsTo<Federacion, $this>
     */
    public function federacion(): BelongsTo
    {
        return $this->belongsTo(Federacion::class);
    }

    /**
     * Ciclos que trabajan esta habilidad.
     *
     * @return HasMany<Ciclo, $this>
     */
    public function ciclos(): HasMany
    {
        return $this->hasMany(Ciclo::class);
    }
}
