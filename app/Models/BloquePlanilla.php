<?php

namespace App\Models;

use App\Enums\TipoBloque;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bloque de una planilla. Transversal (la planilla es contenido compartido).
 */
class BloquePlanilla extends Model
{
    /**
     * @var string
     */
    protected $table = 'bloques_planilla';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'planilla_id',
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
     * @return BelongsTo<Planilla, $this>
     */
    public function planilla(): BelongsTo
    {
        return $this->belongsTo(Planilla::class);
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
