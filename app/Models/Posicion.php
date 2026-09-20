<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Posición (postura) de la leyenda de formas del Manual ATA Legacy. Catálogo de
 * la federación (sin grupo_id).
 */
class Posicion extends Model
{
    /**
     * @var string
     */
    protected $table = 'posiciones';

    /**
     * @var list<string>
     */
    protected $fillable = ['federacion_id', 'codigo', 'nombre', 'fuente', 'verificado'];

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
