<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Grupo de edad de competencia (catálogo de la federación).
 */
class GrupoEdad extends Model
{
    /**
     * @var string
     */
    protected $table = 'grupos_edad';

    /**
     * @var list<string>
     */
    protected $fillable = ['federacion_id', 'nombre', 'edad_desde', 'edad_hasta', 'orden', 'fuente', 'verificado'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['edad_desde' => 'integer', 'edad_hasta' => 'integer', 'orden' => 'integer', 'verificado' => 'boolean'];
    }

    /**
     * @param  Builder<GrupoEdad>  $query
     * @return Builder<GrupoEdad>
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
