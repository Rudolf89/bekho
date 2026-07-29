<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Respuesta a una pregunta dentro de un intento: qué opciones eligió el usuario
 * y si acertó. Cuelga de un intento (operativo).
 */
class RespuestaIntento extends Model
{
    protected $table = 'respuestas_intento';

    /**
     * @var list<string>
     */
    protected $fillable = ['intento_id', 'pregunta_id', 'correcta'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['correcta' => 'boolean'];
    }

    /**
     * @return BelongsTo<IntentoCuestionario, $this>
     */
    public function intento(): BelongsTo
    {
        return $this->belongsTo(IntentoCuestionario::class, 'intento_id');
    }

    /**
     * @return BelongsTo<PreguntaCuestionario, $this>
     */
    public function pregunta(): BelongsTo
    {
        return $this->belongsTo(PreguntaCuestionario::class, 'pregunta_id');
    }

    /**
     * Opciones elegidas por el usuario.
     *
     * @return BelongsToMany<OpcionPregunta, $this>
     */
    public function opciones(): BelongsToMany
    {
        return $this->belongsToMany(OpcionPregunta::class, 'opcion_respuesta_intento', 'respuesta_id', 'opcion_id');
    }
}
