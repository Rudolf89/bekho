<?php

namespace App\Models;

use App\Enums\TipoPrograma;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catálogo compartido de programas ATA. NO usa el trait PerteneceGrupo: es
 * transversal a todos los grupos (como cargos_rangos).
 *
 * Un alumno puede inscribirse en VARIOS programas, además de su grupo etario.
 * Los programas no son grupos de edad (p. ej. Xtreme cruza todas las edades).
 *
 * OJO — distinción importante (no es duplicado): los programas de formación
 * "Leadership" y "Legacy" comparten nombre con RANGOS del catálogo
 * cargos_rangos. Son cosas distintas y ambas coexisten a propósito:
 *   - El PROGRAMA Legacy es la RUTA (niveles, coursework, horas) en la que el
 *     alumno se inscribe.
 *   - El RANGO Legado (cargos_rangos) es la META que se ALCANZA al completarlo,
 *     con su collar.
 * Los programas de tipo formación se conectan con el módulo de Formación (LMS).
 */
class Programa extends Model
{
    /**
     * Atributos asignables masivamente.
     *
     * @var list<string>
     */
    protected $fillable = [
        'federacion_id',
        'nombre',
        'descripcion',
        'tipo',
        'edad_minima',
        'grado_minimo_id',
        'activo',
        'orden',
        'fuente',
        'verificado',
    ];

    /**
     * Casts de atributos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoPrograma::class,
            'activo' => 'boolean',
            'verificado' => 'boolean',
        ];
    }

    /**
     * Federación dueña del catálogo.
     *
     * @return BelongsTo<Federacion, $this>
     */
    public function federacion(): BelongsTo
    {
        return $this->belongsTo(Federacion::class);
    }

    /**
     * Grado mínimo para INGRESAR al programa (opcional; además de edad_minima).
     *
     * @return BelongsTo<Grado, $this>
     */
    public function gradoMinimo(): BelongsTo
    {
        return $this->belongsTo(Grado::class, 'grado_minimo_id');
    }

    /**
     * Etapas de la ruta formativa, ordenadas.
     *
     * @return HasMany<EtapaPrograma, $this>
     */
    public function etapas(): HasMany
    {
        return $this->hasMany(EtapaPrograma::class)->orderBy('orden');
    }

    /**
     * Solo programas activos.
     *
     * @param  Builder<Programa>  $query
     * @return Builder<Programa>
     */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    /**
     * Ordenados por el campo orden.
     *
     * @param  Builder<Programa>  $query
     * @return Builder<Programa>
     */
    public function scopeOrdenados(Builder $query): Builder
    {
        return $query->orderBy('orden');
    }
}
