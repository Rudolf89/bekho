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

        // Tipos de cargo. El código es la marca estable (no depende del nombre).
        $tipos = [
            ['nombre' => 'Matrícula', 'codigo' => 'matricula', 'recurrente' => false, 'requiere_periodo' => false],
            ['nombre' => 'Mensualidad', 'codigo' => 'mensualidad', 'recurrente' => true, 'requiere_periodo' => true],
            ['nombre' => 'Examen de grado', 'codigo' => 'examen_grado', 'recurrente' => false, 'requiere_periodo' => false],
            ['nombre' => 'Torneo', 'codigo' => 'torneo', 'recurrente' => false, 'requiere_periodo' => false],
            ['nombre' => 'Seminario', 'codigo' => 'seminario', 'recurrente' => false, 'requiere_periodo' => false],
            ['nombre' => 'Campamento', 'codigo' => 'campamento', 'recurrente' => false, 'requiere_periodo' => false],
            ['nombre' => 'Uniforme', 'codigo' => 'uniforme', 'recurrente' => false, 'requiere_periodo' => false],
            ['nombre' => 'Otras actividades', 'codigo' => 'otras', 'recurrente' => false, 'requiere_periodo' => false],
        ];
        foreach ($tipos as $orden => $t) {
            TipoCargo::updateOrCreate(
                ['federacion_id' => $federacion->id, 'nombre' => $t['nombre']],
                [
                    'codigo' => $t['codigo'],
                    'recurrente' => $t['recurrente'],
                    'requiere_periodo' => $t['requiere_periodo'],
                    'orden' => $orden + 1,
                    'activo' => true,
                ],
            );
        }
    }
}
