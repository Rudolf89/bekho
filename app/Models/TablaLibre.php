<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tabla de libres (cuántos libres corresponden según la cantidad de competidores),
 * catálogo de la federación.
 */
class TablaLibre extends Model
{
    /**
     * @var string
     */
    protected $table = 'tabla_libres';

    /**
     * @var list<string>
     */
    protected $fillable = ['federacion_id', 'competidores', 'libres', 'fuente', 'verificado'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['competidores' => 'integer', 'libres' => 'integer', 'verificado' => 'boolean'];
    }

    /**
     * @return BelongsTo<Federacion, $this>
     */
    public function federacion(): BelongsTo
    {
        return $this->belongsTo(Federacion::class);
    }
}
