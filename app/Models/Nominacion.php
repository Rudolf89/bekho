<?php

namespace App\Models;

use App\Enums\EstadoNominacion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Nominación de una persona a un grado que requiere nominación (rojo-negro,
 * danes). Transversal a la federación: no lleva grupo_id. Una nominación aprobada
 * habilita la graduación a ese grado objetivo.
 */
class Nominacion extends Model
{
    /**
     * @var string
     */
    protected $table = 'nominaciones_examen';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'persona_id',
        'grado_objetivo_id',
        'nominado_por_persona_id',
        'fecha',
        'estado',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'estado' => EstadoNominacion::class,
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
     * @return BelongsTo<Grado, $this>
     */
    public function gradoObjetivo(): BelongsTo
    {
        return $this->belongsTo(Grado::class, 'grado_objetivo_id');
    }

    /**
     * @return BelongsTo<Persona, $this>
     */
    public function nominadoPor(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'nominado_por_persona_id');
    }
}
