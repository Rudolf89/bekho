<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Nivel del Programa Legacy (1, 2 o 3). Catálogo compartido (sin grupo_id):
 * define las horas requeridas y sus requisitos.
 */
class NivelLegacy extends Model
{
    protected $table = 'niveles_legacy';

    /**
     * @var list<string>
     */
    protected $fillable = ['nombre', 'orden', 'horas_requeridas', 'edad_minima', 'descripcion', 'fuente', 'verificado'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'horas_requeridas' => 'integer',
            'edad_minima' => 'integer',
            'verificado' => 'boolean',
        ];
    }

    /**
     * @return HasMany<RequisitoLegacy, $this>
     */
    public function requisitos(): HasMany
    {
        return $this->hasMany(RequisitoLegacy::class)->orderBy('orden');
    }

    /**
     * @return HasMany<InscripcionLegacy, $this>
     */
    public function inscripciones(): HasMany
    {
        return $this->hasMany(InscripcionLegacy::class);
    }

    /**
     * @param  Builder<NivelLegacy>  $query
     * @return Builder<NivelLegacy>
     */
    public function scopeOrdenados(Builder $query): Builder
    {
        return $query->orderBy('orden');
    }
}
