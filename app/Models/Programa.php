<?php

namespace App\Models;

use App\Enums\TipoPrograma;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Catálogo compartido de programas ATA. NO usa el trait PerteneceAcademia: es
 * transversal a todas las academias (como cargos_rangos).
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
        'nombre',
        'descripcion',
        'tipo',
        'edad_minima',
        'activo',
        'orden',
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
        ];
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
