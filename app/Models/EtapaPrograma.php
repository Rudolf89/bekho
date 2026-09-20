<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Etapa de un programa: la unidad de la ruta formativa (fusión de los antiguos
 * `niveles` del LMS y `niveles_legacy`). Catálogo de la federación (sin grupo_id).
 *
 * Una etapa agrupa contenidos (estudio) y requisitos de avance, y puede exigir
 * horas, una edad mínima y un grado mínimo para alcanzarla (p. ej. los Niveles
 * Legacy: 100 h y edades 13/16/18, con 1.er Dan en el Nivel 3).
 */
class EtapaPrograma extends Model
{
    protected $table = 'etapas_programa';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'programa_id', 'nombre', 'descripcion', 'orden', 'horas_requeridas',
        'edad_minima', 'grado_minimo_id', 'activo', 'fuente', 'verificado',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'horas_requeridas' => 'integer',
            'edad_minima' => 'integer',
            'activo' => 'boolean',
            'verificado' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Programa, $this>
     */
    public function programa(): BelongsTo
    {
        return $this->belongsTo(Programa::class);
    }

    /**
     * Grado mínimo para alcanzar esta etapa (opcional).
     *
     * @return BelongsTo<Grado, $this>
     */
    public function gradoMinimo(): BelongsTo
    {
        return $this->belongsTo(Grado::class, 'grado_minimo_id');
    }

    /**
     * Contenidos de estudio de la etapa, ordenados.
     *
     * @return HasMany<Contenido, $this>
     */
    public function contenidos(): HasMany
    {
        return $this->hasMany(Contenido::class)->orderBy('orden');
    }

    /**
     * Requisitos de avance de la etapa, ordenados.
     *
     * @return HasMany<RequisitoEtapa, $this>
     */
    public function requisitos(): HasMany
    {
        return $this->hasMany(RequisitoEtapa::class)->orderBy('orden');
    }

    /**
     * Cuestionarios enlazados directamente a la etapa.
     *
     * @return HasMany<Cuestionario, $this>
     */
    public function cuestionarios(): HasMany
    {
        return $this->hasMany(Cuestionario::class);
    }

    /**
     * @param  Builder<EtapaPrograma>  $query
     * @return Builder<EtapaPrograma>
     */
    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    /**
     * @param  Builder<EtapaPrograma>  $query
     * @return Builder<EtapaPrograma>
     */
    public function scopeOrdenadas(Builder $query): Builder
    {
        return $query->orderBy('orden');
    }
}
