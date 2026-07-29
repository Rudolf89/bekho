<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pregunta de un cuestionario. Una o varias de sus opciones pueden ser correctas
 * (la pregunta se cuenta buena solo si el conjunto elegido coincide exactamente).
 * Catálogo compartido (cuelga del cuestionario).
 */
class PreguntaCuestionario extends Model
{
    protected $table = 'preguntas_cuestionario';

    /**
     * @var list<string>
     */
    protected $fillable = ['cuestionario_id', 'enunciado', 'explicacion', 'nota', 'orden'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['orden' => 'integer'];
    }

    /**
     * @return BelongsTo<Cuestionario, $this>
     */
    public function cuestionario(): BelongsTo
    {
        return $this->belongsTo(Cuestionario::class);
    }

    /**
     * @return HasMany<OpcionPregunta, $this>
     */
    public function opciones(): HasMany
    {
        return $this->hasMany(OpcionPregunta::class, 'pregunta_id')->orderBy('orden');
    }

    /**
     * IDs de las opciones correctas de la pregunta.
     *
     * @return list<int>
     */
    public function idsCorrectos(): array
    {
        return $this->opciones->where('correcta', true)->pluck('id')->all();
    }

    /**
     * ¿La pregunta admite varias respuestas correctas?
     */
    public function esMultiple(): bool
    {
        return count($this->idsCorrectos()) > 1;
    }

    /**
     * Evalúa si un conjunto de opciones elegidas responde correctamente.
     *
     * @param  list<int>  $opcionesElegidas
     */
    public function esCorrecta(array $opcionesElegidas): bool
    {
        $correctas = $this->idsCorrectos();
        $elegidas = array_values(array_unique(array_map('intval', $opcionesElegidas)));

        sort($correctas);
        sort($elegidas);

        return $correctas === $elegidas && $correctas !== [];
    }

    /**
     * Opciones correctas (para mostrar la solución).
     *
     * @return Collection<int, OpcionPregunta>
     */
    public function opcionesCorrectas(): Collection
    {
        return $this->opciones->where('correcta', true)->values();
    }
}
