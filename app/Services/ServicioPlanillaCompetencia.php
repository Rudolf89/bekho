<?php

namespace App\Services;

use App\Models\CompetidorPlanilla;
use App\Models\CriterioPrueba;
use App\Models\PlanillaCompetencia;
use App\Models\PuntajePlanilla;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Lógica de las planillas de competencia: registrar puntajes (con las reglas de
 * la escala 9.1–9.9 y el 0 como penalización del central) y calcular el ranking.
 */
class ServicioPlanillaCompetencia
{
    /**
     * Registra (o actualiza) el puntaje de un competidor en un criterio.
     *
     * Reglas: el valor válido es 91–99 (décimas de 9.1 a 9.9). El 0 solo se
     * admite si el criterio lo permite (penalización del juez central).
     */
    public function registrarPuntaje(CompetidorPlanilla $competidor, CriterioPrueba $criterio, int $puntaje): PuntajePlanilla
    {
        $valido = ($puntaje >= 91 && $puntaje <= 99)
            || ($puntaje === 0 && $criterio->permite_cero);

        if (! $valido) {
            throw ValidationException::withMessages([
                'puntaje' => $criterio->permite_cero
                    ? 'El puntaje debe ir de 9.1 a 9.9, o 0 como penalización.'
                    : 'El puntaje debe ir de 9.1 a 9.9.',
            ]);
        }

        return PuntajePlanilla::updateOrCreate(
            ['competidor_planilla_id' => $competidor->id, 'criterio_prueba_id' => $criterio->id],
            ['puntaje' => $puntaje],
        );
    }

    /**
     * Competidores de la planilla ordenados por total descendente (ranking), con
     * el total ya calculado en el atributo "total".
     *
     * @return Collection<int, CompetidorPlanilla>
     */
    public function ranking(PlanillaCompetencia $planilla): Collection
    {
        return $planilla->competidores()
            ->with('puntajes')
            ->get()
            ->map(function (CompetidorPlanilla $c) {
                $c->setAttribute('total', $c->total());

                return $c;
            })
            ->sortByDesc('total')
            ->values();
    }
}
