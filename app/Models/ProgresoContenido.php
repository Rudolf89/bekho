<?php

namespace App\Models;

use App\Enums\EstadoProgreso;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Progreso de una PERSONA sobre un contenido de estudio. Transversal (sin
 * grupo_id): los menores no tienen cuenta, así que el avance cuelga de la
 * persona; el user que lo registró queda como dato aparte.
 */
class ProgresoContenido extends Model
{
    protected $table = 'progreso_contenidos';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'persona_id',
        'registrado_por_user_id',
        'contenido_id',
        'estado',
        'visto_en',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado' => EstadoProgreso::class,
            'visto_en' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Persona, $this>
     */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    /**
     * @return BelongsTo<Contenido, $this>
     */
    public function contenido(): BelongsTo
    {
        return $this->belongsTo(Contenido::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_user_id');
    }
}
