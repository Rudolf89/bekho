<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tramo de entrenamiento (catálogo de la federación): principiantes, intermedio,
 * avanzado, rojo-negro, danes. Reemplaza al enum NivelEntrenamiento.
 */
class TramoEntrenamiento extends Model
{
    /**
     * @var string
     */
    protected $table = 'tramos_entrenamiento';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'federacion_id',
        'clave',
        'nombre',
        'orden',
        'color',
        'activo',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'activo' => 'boolean',
        ];
    }

    /**
     * @param  Builder<TramoEntrenamiento>  $query
     * @return Builder<TramoEntrenamiento>
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
}
