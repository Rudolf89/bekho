<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Prueba de competencia (formas, armas, combate), catálogo de la federación.
 */
class Prueba extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = ['federacion_id', 'nombre', 'modalidad', 'orden'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['orden' => 'integer'];
    }

    /**
     * @param  Builder<Prueba>  $query
     * @return Builder<Prueba>
     */
    public function scopeOrdenados(Builder $query): Builder
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
     * @return HasMany<CriterioPrueba, $this>
     */
    public function criterios(): HasMany
    {
        return $this->hasMany(CriterioPrueba::class)->orderBy('orden');
    }
}
