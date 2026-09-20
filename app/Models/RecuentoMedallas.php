<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Recuento de medallas de una planilla de competencia.
 */
class RecuentoMedallas extends Model
{
    /**
     * @var string
     */
    protected $table = 'recuento_medallas';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'planilla_id',
        'primer_lugar',
        'segundo_lugar',
        'tercer_lugar',
        'participacion',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'primer_lugar' => 'integer',
            'segundo_lugar' => 'integer',
            'tercer_lugar' => 'integer',
            'participacion' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<PlanillaCompetencia, $this>
     */
    public function planilla(): BelongsTo
    {
        return $this->belongsTo(PlanillaCompetencia::class, 'planilla_id');
    }
}
