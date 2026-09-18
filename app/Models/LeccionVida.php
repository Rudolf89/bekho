<?php

namespace App\Models;

use App\Enums\HabilidadVida;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lección de Vida (ATA Legacy): una por semana DENTRO de un ciclo, con tres
 * momentos (comienzo, durante, fin), cada uno con texto y frase destacada. La
 * Habilidad para la Vida se deriva del ciclo. Catálogo compartido (sin grupo_id).
 */
class LeccionVida extends Model
{
    protected $table = 'lecciones_vida';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'ciclo_id', 'semana',
        'comienzo_texto', 'comienzo_frase',
        'durante_texto', 'durante_frase',
        'fin_texto', 'fin_frase',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'semana' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Ciclo, $this>
     */
    public function ciclo(): BelongsTo
    {
        return $this->belongsTo(Ciclo::class);
    }

    /**
     * Habilidad para la Vida de la lección (la de su ciclo).
     */
    public function getHabilidadAttribute(): ?HabilidadVida
    {
        return $this->ciclo?->habilidad_vida;
    }

    /**
     * Los tres momentos de la clase como lista para la interfaz.
     *
     * @return list<array{clave: string, etiqueta: string, texto: ?string, frase: ?string}>
     */
    public function momentos(): array
    {
        return [
            ['clave' => 'comienzo', 'etiqueta' => 'Comienzo de la clase', 'texto' => $this->comienzo_texto, 'frase' => $this->comienzo_frase],
            ['clave' => 'durante', 'etiqueta' => 'Durante la clase', 'texto' => $this->durante_texto, 'frase' => $this->durante_frase],
            ['clave' => 'fin', 'etiqueta' => 'Fin de la clase', 'texto' => $this->fin_texto, 'frase' => $this->fin_frase],
        ];
    }
}
