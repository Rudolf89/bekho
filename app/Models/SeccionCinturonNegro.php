<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ítem de una sección del planificador de Cinturón Negro. Catálogo compartido.
 */
class SeccionCinturonNegro extends Model
{
    protected $table = 'secciones_cinturon_negro';

    /**
     * @var list<string>
     */
    protected $fillable = ['planificacion_cinturon_negro_id', 'seccion', 'item', 'orden'];

    /**
     * @return BelongsTo<PlanificacionCinturonNegro, $this>
     */
    public function planificacion(): BelongsTo
    {
        return $this->belongsTo(PlanificacionCinturonNegro::class, 'planificacion_cinturon_negro_id');
    }
}
