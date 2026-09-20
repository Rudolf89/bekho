<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tipo de cargo de la federación (catálogo de cobros).
 */
class TipoCargo extends Model
{
    /**
     * @var string
     */
    protected $table = 'tipos_cargo';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'federacion_id',
        'nombre',
        'codigo',
        'recurrente',
        'requiere_periodo',
        'orden',
        'activo',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recurrente' => 'boolean',
            'requiere_periodo' => 'boolean',
            'orden' => 'integer',
            'activo' => 'boolean',
        ];
    }

    /**
     * @param  Builder<TipoCargo>  $query
     * @return Builder<TipoCargo>
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
