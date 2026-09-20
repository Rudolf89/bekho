<?php

namespace App\Models;

use App\Enums\TipoAtributoTecnico;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Atributo técnico (rúbrica) o criterio de conocimiento de forma del Manual ATA
 * Legacy, distinguidos por `tipo`. Catálogo de la federación (sin grupo_id).
 */
class AtributoTecnico extends Model
{
    /**
     * @var string
     */
    protected $table = 'atributos_tecnicos';

    /**
     * @var list<string>
     */
    protected $fillable = ['federacion_id', 'tipo', 'nombre', 'definicion', 'orden', 'fuente', 'verificado'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoAtributoTecnico::class,
            'orden' => 'integer',
            'verificado' => 'boolean',
        ];
    }

    /**
     * @param  Builder<AtributoTecnico>  $query
     * @return Builder<AtributoTecnico>
     */
    public function scopeDeTipo(Builder $query, TipoAtributoTecnico $tipo): Builder
    {
        return $query->where('tipo', $tipo->value);
    }

    /**
     * @return BelongsTo<Federacion, $this>
     */
    public function federacion(): BelongsTo
    {
        return $this->belongsTo(Federacion::class);
    }
}
