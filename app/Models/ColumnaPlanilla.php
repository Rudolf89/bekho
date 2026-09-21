<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Columna de una planilla imprimible: el encabezado de una de las casillas que
 * se llenan a mano. El orden es el de impresión, de izquierda a derecha.
 */
class ColumnaPlanilla extends Model
{
    protected $table = 'columnas_planilla';

    /**
     * @var list<string>
     */
    protected $fillable = ['planilla_id', 'titulo', 'orden'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['orden' => 'integer'];
    }

    /**
     * @return BelongsTo<Planilla, $this>
     */
    public function planilla(): BelongsTo
    {
        return $this->belongsTo(Planilla::class);
    }
}
