<?php

namespace App\Models;

use App\Enums\HabilidadVida;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Ciclo del currículo ATA: uno por Habilidad para la Vida Songahm (6 en total),
 * de 8 semanas. Es la columna vertebral que organiza lecciones de vida y class
 * planners. Catálogo compartido (sin academia_id).
 */
class Ciclo extends Model
{
    protected $table = 'ciclos';

    /**
     * @var list<string>
     */
    protected $fillable = ['habilidad_vida', 'nombre', 'orden', 'semanas'];

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
     * Lecciones de vida de este ciclo, por semana.
     *
     * @return HasMany<LeccionVida, $this>
     */
    public function lecciones(): HasMany
    {
        return $this->hasMany(LeccionVida::class)->orderBy('semana');
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
