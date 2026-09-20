<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Forma (poomsae) del currículo Songahm: secuencia con nombre coreano,
 * significado y grado. Catálogo de la federación (sin grupo_id), con procedencia.
 * Es distinta de una técnica (un movimiento): sus movimientos viven en pasos_forma.
 */
class Forma extends Model
{
    /**
     * @var string
     */
    protected $table = 'formas';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'federacion_id', 'nombre', 'nombre_coreano', 'significado',
        'grado_id', 'orden', 'activo', 'fuente', 'verificado',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'activo' => 'boolean',
            'verificado' => 'boolean',
        ];
    }

    /**
     * @param  Builder<Forma>  $query
     * @return Builder<Forma>
     */
    public function scopeOrdenadas(Builder $query): Builder
    {
        return $query->orderBy('orden');
    }

    /**
     * Movimientos de la forma, en orden.
     *
     * @return HasMany<PasoForma, $this>
     */
    public function pasos(): HasMany
    {
        return $this->hasMany(PasoForma::class)->orderBy('numero');
    }

    /**
     * @return BelongsTo<Federacion, $this>
     */
    public function federacion(): BelongsTo
    {
        return $this->belongsTo(Federacion::class);
    }

    /**
     * @return BelongsTo<Grado, $this>
     */
    public function grado(): BelongsTo
    {
        return $this->belongsTo(Grado::class);
    }
}
