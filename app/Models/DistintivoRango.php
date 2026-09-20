<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Distintivo de collar de un rango: escalón (por umbral de graduados acumulados)
 * dentro de un rango. Catálogo de la federación (sin grupo_id). El distintivo
 * ACTUAL de una persona no se guarda: se calcula contando sus créditos contra
 * estos umbrales (ver App\Services\ServicioCreditos). graduados_requeridos es
 * nullable: mientras la federación no fije el umbral, ese escalón no se otorga.
 */
class DistintivoRango extends Model
{
    /**
     * @var string
     */
    protected $table = 'distintivos_rango';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'federacion_id',
        'rango_id',
        'nombre',
        'graduados_requeridos',
        'orden',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'graduados_requeridos' => 'integer',
            'orden' => 'integer',
        ];
    }

    /**
     * @param  Builder<DistintivoRango>  $query
     * @return Builder<DistintivoRango>
     */
    public function scopeOrdenados(Builder $query): Builder
    {
        return $query->orderBy('orden');
    }

    /**
     * @return BelongsTo<Federacion, $this>
     */
    public function federacion(): BelongsTo
    {
        return $this->belongsTo(Federacion::class);
    }

    /**
     * @return BelongsTo<CargoRango, $this>
     */
    public function rango(): BelongsTo
    {
        return $this->belongsTo(CargoRango::class, 'rango_id');
    }
}
