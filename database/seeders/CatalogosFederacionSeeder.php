<?php

namespace Database\Seeders;

use App\Models\EscalaPuntaje;
use App\Models\Federacion;
use App\Models\TipoCargo;
use Illuminate\Database\Seeder;

/**
 * Rediseño Fase 3: catálogos configurables de la federación que aún no tenían
 * tabla propia: escalas de puntaje (competencia, rúbrica) y tipos de cargo
 * (cobros). Idempotente.
 */
class CatalogosFederacionSeeder extends Seeder
{
    public function run(): void
    {
        $federacion = Federacion::firstOrCreate(
            ['nombre' => 'BEKHO'],
            ['razon_social' => 'BEKHO Martial Arts', 'pais' => 'Chile', 'moneda' => 'CLP', 'activo' => true],
        );

        // Escalas de puntaje.
        $escalas = [
            ['nombre' => 'Competencia', 'minimo' => 9.1, 'maximo' => 9.9, 'paso' => 0.1],
            ['nombre' => 'Rúbrica', 'minimo' => 0, 'maximo' => 6.0, 'paso' => 0.1],
        ];
        foreach ($escalas as $e) {
            EscalaPuntaje::updateOrCreate(
                ['federacion_id' => $federacion->id, 'nombre' => $e['nombre']],
                ['minimo' => $e['minimo'], 'maximo' => $e['maximo'], 'paso' => $e['paso'], 'activo' => true],
            );
        }

        // Tipos de cargo.
        $tipos = [
            ['nombre' => 'Matrícula', 'recurrente' => false, 'requiere_periodo' => false],
            ['nombre' => 'Mensualidad', 'recurrente' => true, 'requiere_periodo' => true],
            ['nombre' => 'Examen de grado', 'recurrente' => false, 'requiere_periodo' => false],
            ['nombre' => 'Torneo', 'recurrente' => false, 'requiere_periodo' => false],
            ['nombre' => 'Seminario', 'recurrente' => false, 'requiere_periodo' => false],
            ['nombre' => 'Campamento', 'recurrente' => false, 'requiere_periodo' => false],
            ['nombre' => 'Uniforme', 'recurrente' => false, 'requiere_periodo' => false],
            ['nombre' => 'Otras actividades', 'recurrente' => false, 'requiere_periodo' => false],
        ];
        foreach ($tipos as $orden => $t) {
            TipoCargo::updateOrCreate(
                ['federacion_id' => $federacion->id, 'nombre' => $t['nombre']],
                [
                    'recurrente' => $t['recurrente'],
                    'requiere_periodo' => $t['requiere_periodo'],
                    'orden' => $orden + 1,
                    'activo' => true,
                ],
            );
        }
    }
}
