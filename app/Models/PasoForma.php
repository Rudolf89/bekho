<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Movimiento de una forma. La sección de altura y los modificadores (KIHAP,
 * tensión, lento, con retracción, doble acción) y combinadores (& y /) se guardan
 * como texto; la posición (postura) se referencia al catálogo de posiciones.
 */
class PasoForma extends Model
{
    /**
     * @var string
     */
    protected $table = 'pasos_forma';

    /**
     * @var list<string>
     */
    protected $fillable = ['forma_id', 'numero', 'lado', 'tecnica', 'posicion_id', 'seccion', 'modificadores', 'orden'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'numero' => 'integer',
            'orden' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Forma, $this>
     */
    public function forma(): BelongsTo
    {
        return $this->belongsTo(Forma::class);
    }

    /**
     * @return BelongsTo<Posicion, $this>
     */
    public function posicion(): BelongsTo
    {
        return $this->belongsTo(Posicion::class);
    }
}
