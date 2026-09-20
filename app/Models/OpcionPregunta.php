<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Opción de una pregunta. Catálogo compartido (cuelga de la pregunta).
 */
class OpcionPregunta extends Model
{
    protected $table = 'opciones_pregunta';

    /**
     * @var list<string>
     */
    protected $fillable = ['pregunta_id', 'texto', 'correcta', 'orden'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'correcta' => 'boolean',
            'orden' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<PreguntaCuestionario, $this>
     */
    public function pregunta(): BelongsTo
    {
        return $this->belongsTo(PreguntaCuestionario::class, 'pregunta_id');
    }
}
