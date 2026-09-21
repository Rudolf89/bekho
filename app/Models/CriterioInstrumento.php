<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Criterio puntuable de una sección de instrumento. Puede corresponder a un
 * atributo técnico del catálogo (los 10 atributos + los 3 criterios de forma).
 */
class CriterioInstrumento extends Model
{
    protected $table = 'criterios_instrumento';

    /**
     * @var list<string>
     */
    protected $fillable = ['seccion_instrumento_id', 'nombre', 'valor', 'atributo_tecnico_id', 'orden'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['valor' => 'decimal:2'];
    }

    /**
     * @return BelongsTo<SeccionInstrumento, $this>
     */
    public function seccion(): BelongsTo
    {
        return $this->belongsTo(SeccionInstrumento::class, 'seccion_instrumento_id');
    }

    /**
     * Puntajes ya registrados sobre este criterio: si existen, el criterio no se
     * puede retirar del instrumento sin perder historial.
     *
     * @return HasMany<PuntajeCriterio, $this>
     */
    public function puntajes(): HasMany
    {
        return $this->hasMany(PuntajeCriterio::class, 'criterio_instrumento_id');
    }

    /**
     * @return BelongsTo<AtributoTecnico, $this>
     */
    public function atributo(): BelongsTo
    {
        return $this->belongsTo(AtributoTecnico::class, 'atributo_tecnico_id');
    }
}
