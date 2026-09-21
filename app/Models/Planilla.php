<?php

namespace App\Models;

use App\Enums\EstadoPlanilla;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Planilla imprimible del programa: el formulario en papel que se llena en la
 * cancha. Catálogo de la federación (sin grupo_id). Sus columnas definen la
 * grilla que se imprime en blanco.
 *
 * No confundir con PlanillaCompetencia (planillas_competencia), que es la
 * planilla operativa de un torneo con sus jueces y competidores.
 */
class Planilla extends Model
{
    protected $table = 'planillas';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'federacion_id', 'nombre', 'uso', 'descripcion', 'estado',
        'version', 'filas', 'activo', 'orden', 'fuente', 'verificado',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado' => EstadoPlanilla::class,
            'version' => 'integer',
            'filas' => 'integer',
            'orden' => 'integer',
            'activo' => 'boolean',
            'verificado' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Federacion, $this>
     */
    public function federacion(): BelongsTo
    {
        return $this->belongsTo(Federacion::class);
    }

    /**
     * @return HasMany<ColumnaPlanilla, $this>
     */
    public function columnas(): HasMany
    {
        return $this->hasMany(ColumnaPlanilla::class)->orderBy('orden');
    }

    /**
     * Etiqueta corta de versión para el badge: v1, v2…
     */
    public function etiquetaVersion(): string
    {
        return 'v'.$this->version;
    }

    /**
     * @param  Builder<Planilla>  $query
     * @return Builder<Planilla>
     */
    public function scopeOrdenadas(Builder $query): Builder
    {
        return $query->orderBy('orden')->orderBy('nombre');
    }
}
