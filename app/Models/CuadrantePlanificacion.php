<?php

namespace App\Models;

use App\Enums\Cuadrante;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cuadrante de enseñanza de una planificación de clase. Transversal (contenido
 * compartido).
 */
class CuadrantePlanificacion extends Model
{
    /**
     * @var string
     */
    protected $table = 'cuadrantes_planificacion';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'planificacion_clase_id',
        'cuadrante',
        'nota',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cuadrante' => Cuadrante::class,
        ];
    }

    /**
     * @return BelongsTo<PlanificacionClase, $this>
     */
    public function planificacion(): BelongsTo
    {
        return $this->belongsTo(PlanificacionClase::class, 'planificacion_clase_id');
    }
}
