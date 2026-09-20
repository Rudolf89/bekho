<?php

namespace Database\Seeders;

use App\Models\CompetidorPlanilla;
use App\Models\JuezPlanilla;
use App\Models\PlanillaCompetencia;
use App\Models\Prueba;
use App\Services\ServicioPlanillaCompetencia;
use Illuminate\Database\Seeder;

/**
 * Planilla de competencia de demostración (certificación de planillero): una
 * prueba de formas con competidores y jueces FICTICIOS. Idempotente.
 */
class DemoCompetenciaSeeder extends Seeder
{
    public function run(): void
    {
        if (PlanillaCompetencia::exists()) {
            return;
        }

        $prueba = Prueba::with('criterios')->orderBy('orden')->first();
        if (! $prueba || $prueba->criterios->isEmpty()) {
            return;
        }

        $planilla = PlanillaCompetencia::create([
            'prueba_id' => $prueba->id,
            'fecha' => now()->toDateString(),
            'genero' => 'Mixto',
            'nro_pista' => '1',
            'estado' => 'borrador',
        ]);

        foreach ([['central', 'Juez Demo Central'], ['a', 'Juez Demo A'], ['b', 'Juez Demo B'], ['planillero', 'Planillero Demo']] as [$papel, $nombre]) {
            JuezPlanilla::create(['planilla_id' => $planilla->id, 'papel' => $papel, 'nombre' => $nombre]);
        }

        $servicio = app(ServicioPlanillaCompetencia::class);
        $nombres = ['Competidor Uno', 'Competidor Dos', 'Competidor Tres'];

        foreach ($nombres as $i => $nombre) {
            $competidor = CompetidorPlanilla::create([
                'planilla_id' => $planilla->id,
                'orden' => $i + 1,
                'nombre' => $nombre,
                'edad' => 12 + $i,
                'pais' => 'Chile',
            ]);

            foreach ($prueba->criterios as $j => $criterio) {
                // Puntajes de demo variados (9.5–9.9 en décimas).
                $servicio->registrarPuntaje($competidor, $criterio, 95 + (($i + $j) % 5));
            }
        }
    }
}
