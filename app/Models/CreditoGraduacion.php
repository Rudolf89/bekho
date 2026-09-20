<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Crédito de graduación: una fila por cada persona de la cadena de supervisión a
 * la que una graduación suma un punto. posicion 1 = origen (instructor
 * acreditado); 2, 3, … suben por la línea. Es transversal (sin grupo_id): la
 * cadena puede cruzar grupos.
 */
class CreditoGraduacion extends Model
{
    /**
     * @var string
     */
    protected $table = 'creditos_graduacion';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'graduacion_id',
        'persona_id',
        'posicion',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'posicion' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Graduacion, $this>
     */
    public function graduacion(): BelongsTo
    {
        return $this->belongsTo(Graduacion::class);
    }

    /**
     * @return BelongsTo<Persona, $this>
     */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }
}
