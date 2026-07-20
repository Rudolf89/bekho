<?php

namespace App\Models;

use App\Enums\HabilidadVida;
use Illuminate\Database\Eloquent\Model;

/**
 * Lección de Vida (ATA Legacy): una por semana, con una Habilidad para la Vida y
 * tres momentos (comienzo, durante, fin), cada uno con texto y frase destacada.
 * Catálogo compartido (sin academia_id).
 */
class LeccionVida extends Model
{
    protected $table = 'lecciones_vida';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'semana', 'habilidad',
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
            'habilidad' => HabilidadVida::class,
        ];
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
