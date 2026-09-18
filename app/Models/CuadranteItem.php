<?php

namespace App\Models;

use App\Enums\Cuadrante;
use App\Enums\RolCuadrante;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Ítem (responsabilidad) de un Cuadrante de Enseñanza, para el alumno o el
 * instructor. Catálogo compartido (sin grupo_id).
 */
class CuadranteItem extends Model
{
    protected $table = 'cuadrante_items';

    /**
     * @var list<string>
     */
    protected $fillable = ['cuadrante', 'rol', 'orden', 'texto', 'detalle'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cuadrante' => Cuadrante::class,
            'rol' => RolCuadrante::class,
        ];
    }

    /**
     * @param  Builder<CuadranteItem>  $query
     * @return Builder<CuadranteItem>
     */
    public function scopeDeCuadrante(Builder $query, Cuadrante|string $cuadrante): Builder
    {
        return $query->where('cuadrante', $cuadrante instanceof Cuadrante ? $cuadrante->value : $cuadrante);
    }
}
