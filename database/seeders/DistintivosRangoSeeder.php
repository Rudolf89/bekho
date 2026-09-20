<?php

namespace Database\Seeders;

use App\Models\CargoRango;
use App\Models\DistintivoRango;
use App\Models\Federacion;
use Illuminate\Database\Seeder;

/**
 * Distintivos de collar del rango de Profesor: la progresión que avanza con los
 * créditos de graduación acumulados. Solo se siembran los NOMBRES; los umbrales
 * (graduados_requeridos) están POR CONFIRMAR por la federación → nulos, no se
 * inventan. Idempotente.
 */
class DistintivosRangoSeeder extends Seeder
{
    public function run(): void
    {
        $federacion = Federacion::firstOrCreate(
            ['nombre' => 'BEKHO'],
            ['razon_social' => 'BEKHO Martial Arts', 'pais' => 'Chile', 'moneda' => 'CLP', 'activo' => true],
        );

        $profesor = CargoRango::where('nombre', 'Profesor')->first();
        if (! $profesor) {
            return;
        }

        $distintivos = ['Negro', 'Negro-Azul-Negro', 'Negro-Plateado-Negro', 'Negro-Dorado-Negro'];

        foreach ($distintivos as $orden => $nombre) {
            DistintivoRango::updateOrCreate(
                ['rango_id' => $profesor->id, 'nombre' => $nombre],
                [
                    'federacion_id' => $federacion->id,
                    'graduados_requeridos' => null, // POR CONFIRMAR: no se inventa
                    'orden' => $orden + 1,
                ],
            );
        }
    }
}
