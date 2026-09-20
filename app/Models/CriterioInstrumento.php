<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Criterio puntuable de una sección de instrumento. Puede corresponder a un
 * atributo técnico del catálogo (los 10 atributos + los 3 criterios de forma).
 */
class CriterioInstrumento extends Model
{
    protected $table = 'criterios_instrumento';

    /**
     * @var list<string>
     */
    protected $fillable = ['seccion_instrumento_id', 'nombre', 'atributo_tecnico_id', 'orden'];

    /**
     * @return BelongsTo<SeccionInstrumento, $this>
     */
    public function seccion(): BelongsTo
    {
        return $this->belongsTo(SeccionInstrumento::class, 'seccion_instrumento_id');
    }

    /**
     * @return BelongsTo<AtributoTecnico, $this>
     */
    public function atributo(): BelongsTo
    {
        return $this->belongsTo(AtributoTecnico::class, 'atributo_tecnico_id');
    }
}
