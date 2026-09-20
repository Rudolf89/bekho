<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Puntaje de un competidor en un criterio de la prueba. Se guarda el valor real
 * en décimas (91–99); el 0 es una penalización que solo puede poner el juez
 * central. El dígito visible es puntaje − 90 (el 0 se muestra y aporta 0).
 */
class PuntajePlanilla extends Model
{
    /**
     * @var string
     */
    protected $table = 'puntajes_planilla';

    /**
     * @var list<string>
     */
    protected $fillable = ['competidor_planilla_id', 'criterio_prueba_id', 'puntaje'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'puntaje' => 'integer',
        ];
    }

    /**
     * Dígito visible del puntaje: 9.x → x (puntaje − 90); el 0 aporta 0.
     */
    public function digito(): int
    {
        return $this->puntaje >= 90 ? $this->puntaje - 90 : 0;
    }

    /**
     * @return BelongsTo<CompetidorPlanilla, $this>
     */
    public function competidor(): BelongsTo
    {
        return $this->belongsTo(CompetidorPlanilla::class, 'competidor_planilla_id');
    }

    /**
     * @return BelongsTo<CriterioPrueba, $this>
     */
    public function criterio(): BelongsTo
    {
        return $this->belongsTo(CriterioPrueba::class, 'criterio_prueba_id');
    }
}
