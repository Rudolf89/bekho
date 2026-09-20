<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Arma del programa Protech (Manual ATA Legacy). Catálogo de la federación (sin
 * grupo_id).
 */
class Arma extends Model
{
    /**
     * @var string
     */
    protected $table = 'armas';

    /**
     * @var list<string>
     */
    protected $fillable = ['federacion_id', 'abreviatura', 'nombre', 'nombre_comun', 'modalidad', 'fuente', 'verificado'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['verificado' => 'boolean'];
    }

    /**
     * @return BelongsTo<Federacion, $this>
     */
    public function federacion(): BelongsTo
    {
        return $this->belongsTo(Federacion::class);
    }
}
