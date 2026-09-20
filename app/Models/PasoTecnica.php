<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Paso (o ítem de segmento) de una técnica con secuencia. Catálogo compartido.
 */
class PasoTecnica extends Model
{
    protected $table = 'pasos_tecnica';

    /**
     * @var list<string>
     */
    protected $fillable = ['tecnica_id', 'segmento', 'lado', 'postura', 'seccion', 'orden', 'texto'];

    /**
     * @return BelongsTo<Tecnica, $this>
     */
    public function tecnica(): BelongsTo
    {
        return $this->belongsTo(Tecnica::class);
    }
}
