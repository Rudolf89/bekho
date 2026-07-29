<?php

namespace App\Models;

use App\Enums\GrupoEtario;
use App\Enums\HabilidadVida;
use App\Enums\TipoRecompensa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Recompensa/insignia del sistema de gamificación. Catálogo compartido (sin
 * academia_id): define qué se puede ganar. Los logros por alumno cuelgan aparte.
 */
class Recompensa extends Model
{
    protected $table = 'recompensas';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tipo', 'nombre', 'descripcion', 'habilidad_vida', 'grupo_etario',
        'color', 'emoji', 'repetible', 'orden', 'activo',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoRecompensa::class,
            'habilidad_vida' => HabilidadVida::class,
            'grupo_etario' => GrupoEtario::class,
            'repetible' => 'boolean',
            'orden' => 'integer',
            'activo' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Logro, $this>
     */
    public function logros(): HasMany
    {
        return $this->hasMany(Logro::class);
    }

    /**
     * @param  Builder<Recompensa>  $query
     * @return Builder<Recompensa>
     */
    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    /**
     * @param  Builder<Recompensa>  $query
     * @return Builder<Recompensa>
     */
    public function scopeOrdenadas(Builder $query): Builder
    {
        return $query->orderBy('tipo')->orderBy('orden');
    }

    /**
     * Recompensas aplicables a un grupo etario (las sin grupo aplican a todos).
     *
     * @param  Builder<Recompensa>  $query
     * @return Builder<Recompensa>
     */
    public function scopeParaGrupo(Builder $query, GrupoEtario $grupo): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->whereNull('grupo_etario')
            ->orWhere('grupo_etario', $grupo->value));
    }
}
