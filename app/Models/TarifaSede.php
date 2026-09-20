<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tarifa de una SEDE para un tipo de cargo, por tramo de cantidad de alumnos.
 * Cada sede define sus propios valores; no hay tarifa a nivel de grupo.
 */
class TarifaSede extends Model
{
    /**
     * @var string
     */
    protected $table = 'tarifas_sede';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'sede_id',
        'tipo_cargo_id',
        'cantidad_alumnos',
        'monto_por_alumno',
        'vigente_desde',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cantidad_alumnos' => 'integer',
            'monto_por_alumno' => 'integer',
            'vigente_desde' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Sede, $this>
     */
    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class);
    }

    /**
     * @return BelongsTo<TipoCargo, $this>
     */
    public function tipoCargo(): BelongsTo
    {
        return $this->belongsTo(TipoCargo::class);
    }
}
