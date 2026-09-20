<?php

namespace App\Services;

use App\Models\Combate;
use App\Models\CompetidorPlanilla;
use App\Models\CriterioPrueba;
use App\Models\MarcaCombate;
use App\Models\PlanillaCompetencia;
use App\Models\PuntajePlanilla;
use App\Models\RecuentoMedallas;
use App\Models\ResultadoPlanilla;
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

    // --- Combate (sparring) --------------------------------------------------

    /**
     * Registra un combate de la planilla entre A y B (B nulo = libre).
     *
     * @param  array<string, mixed>  $opts  ronda, tipo
     */
    public function registrarCombate(PlanillaCompetencia $planilla, CompetidorPlanilla $a, ?CompetidorPlanilla $b = null, array $opts = []): Combate
    {
        return Combate::create([
            'planilla_id' => $planilla->id,
            'orden' => $planilla->combates()->count() + 1,
            'competidor_a_id' => $a->id,
            'competidor_b_id' => $b?->id,
            'ronda' => $opts['ronda'] ?? null,
            'tipo' => $opts['tipo'] ?? null,
        ]);
    }

    /**
     * Registra (o actualiza) la marca de un competidor en un combate.
     */
    public function registrarMarca(Combate $combate, CompetidorPlanilla $competidor, int $puntos, int $advertencias = 0, bool $descalificado = false): MarcaCombate
    {
        return MarcaCombate::updateOrCreate(
            ['combate_id' => $combate->id, 'competidor_planilla_id' => $competidor->id],
            ['puntos' => $puntos, 'advertencias' => $advertencias, 'descalificado' => $descalificado],
        );
    }

    /**
     * Define el ganador del combate. Sin argumento, lo resuelve por puntos entre
     * los no descalificados (null si hay empate o nadie elegible).
     */
    public function definirGanador(Combate $combate, ?CompetidorPlanilla $ganador = null): ?CompetidorPlanilla
    {
        if ($ganador === null) {
            $marcas = $combate->marcas()->where('descalificado', false)->orderByDesc('puntos')->get();

            if ($marcas->count() < 1 || ($marcas->count() >= 2 && $marcas[0]->puntos === $marcas[1]->puntos)) {
                return null;
            }

            $ganador = $marcas->first()->competidor;
        }

        $combate->update(['ganador_id' => $ganador?->id]);

        return $ganador;
    }

    // --- Resultados y medallas ----------------------------------------------

    /**
     * Registra (o actualiza) el lugar de un competidor en la planilla.
     */
    public function registrarResultado(PlanillaCompetencia $planilla, CompetidorPlanilla $competidor, int $lugar): ResultadoPlanilla
    {
        return ResultadoPlanilla::updateOrCreate(
            ['planilla_id' => $planilla->id, 'competidor_planilla_id' => $competidor->id],
            ['lugar' => $lugar],
        );
    }

    /**
     * Recalcula el recuento de medallas desde los resultados: cuántos 1.º, 2.º y
     * 3.º lugar, y participación = total de competidores.
     */
    public function recalcularMedallas(PlanillaCompetencia $planilla): RecuentoMedallas
    {
        $resultados = $planilla->resultados()->get();

        return RecuentoMedallas::updateOrCreate(
            ['planilla_id' => $planilla->id],
            [
                'primer_lugar' => $resultados->where('lugar', 1)->count(),
                'segundo_lugar' => $resultados->where('lugar', 2)->count(),
                'tercer_lugar' => $resultados->where('lugar', 3)->count(),
                'participacion' => $planilla->competidores()->count(),
            ],
        );
    }
}
