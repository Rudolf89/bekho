<?php

namespace App\Models;

use App\Enums\EscalaGrado;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Catálogo compartido de grados (cinturones). NO usa el trait PerteneceAcademia:
 * es transversal a todas las academias (como cargos_rangos).
 *
 * Soporta múltiples escalas de cinturones: Tigers usa un sistema propio de
 * rangos y parches, distinto al del resto de los grupos. Ver App\Enums\EscalaGrado.
 */
class Grado extends Model
{
    /**
     * Atributos asignables masivamente.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nombre',
        'orden',
        'escala',
        'color',
        'activo',
    ];

    /**
     * Casts de atributos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'escala' => EscalaGrado::class,
            'activo' => 'boolean',
        ];
    }

    /**
     * Filtra por escala de grados.
     *
     * @param  Builder<Grado>  $query
     * @return Builder<Grado>
     */
    public function scopePorEscala(Builder $query, EscalaGrado $escala): Builder
    {
        return $query->where('escala', $escala->value);
    }

    /**
     * Ordenados por el campo orden.
     *
     * @param  Builder<Grado>  $query
     * @return Builder<Grado>
     */
    public function scopeOrdenados(Builder $query): Builder
    {
        return $query->orderBy('orden');
    }
}
