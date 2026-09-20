<?php

namespace App\Models;

use App\Enums\TipoBloque;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bloque de una planificación de clase. Transversal (la planificación es
 * contenido compartido).
 */
class BloquePlanificacion extends Model
{
    /**
     * @var string
     */
    protected $table = 'bloques_planificacion';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'planificacion_clase_id',
        'tipo',
        'tiempo',
        'titulo',
        'contenido',
        'cuadrante_texto',
        'cuadrante_color',
        'orden',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoBloque::class,
        ];
    }

    /**
     * @return BelongsTo<PlanificacionClase, $this>
     */
    public function planificacion(): BelongsTo
    {
        return $this->belongsTo(PlanificacionClase::class, 'planificacion_clase_id');
    }

    /**
     * Nombre visible del bloque: el título explícito si lo hay (Tigers/Cinturón
     * Negro o bloques con nombre propio como "Fórmula: Songahm 3"), o la etiqueta
     * del tipo estándar.
     */
    public function tituloVisible(): string
    {
        return $this->titulo ?: ($this->tipo?->etiqueta() ?? '');
    }
}
