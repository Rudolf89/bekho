<?php

namespace App\Models;

use App\Enums\GrupoEtario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Adaptación por grupo etario de un bloque de semanas de Cinturón Negro.
 */
class AdaptacionCinturonNegro extends Model
{
    protected $table = 'adaptaciones_cinturon_negro';

    /**
     * @var list<string>
     */
    protected $fillable = ['planificacion_cinturon_negro_id', 'grupo_etario', 'texto'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['grupo_etario' => GrupoEtario::class];
    }

    /**
     * @return BelongsTo<PlanificacionCinturonNegro, $this>
     */
    public function planificacion(): BelongsTo
    {
        return $this->belongsTo(PlanificacionCinturonNegro::class, 'planificacion_cinturon_negro_id');
    }
}
