<?php

namespace Database\Seeders;

use App\Models\CategoriaCompetencia;
use App\Models\EscalaPuntaje;
use App\Models\Federacion;
use App\Models\Prueba;
use Illuminate\Database\Seeder;

/**
 * Rediseño Fase 6: catálogos de competencia de la federación. Siembra las
 * categorías (color/negro) y las pruebas con sus criterios de evaluación según
 * la fórmula tradicional y las armas (documento de decisiones). Los grupos de
 * edad y la tabla de libres se cargan con los datos reales de la federación.
 * Idempotente.
 */
class CompetenciaSeeder extends Seeder
{
    public function run(): void
    {
        $federacion = Federacion::firstOrCreate(
            ['nombre' => 'BEKHO'],
            ['razon_social' => 'BEKHO Martial Arts', 'pais' => 'Chile', 'moneda' => 'CLP', 'activo' => true],
        );

        $escalaCompetencia = EscalaPuntaje::where('federacion_id', $federacion->id)
            ->where('nombre', 'Competencia')->first();

        // Categorías.
        foreach ([['Color', 'color'], ['Negro', 'negro']] as $orden => [$nombre, $tipo]) {
            CategoriaCompetencia::updateOrCreate(
                ['federacion_id' => $federacion->id, 'nombre' => $nombre],
                ['tipo' => $tipo, 'orden' => $orden + 1],
            );
        }

        // Pruebas y sus criterios (papel de juez, permite_cero solo en el central).
        $pruebas = [
            ['Formas tradicionales', 'formas', [
                ['a', 'Patadas y posiciones', false],
                ['central', 'General', true],
                ['b', 'Golpes y defensas', false],
            ]],
            ['Armas tradicionales', 'armas', [
                ['a', 'Posiciones y golpes', false],
                ['central', 'Memorización y transición', true],
                ['b', 'Tiempo y fluidez', false],
            ]],
            ['Combate', 'combate', []],
        ];

        foreach ($pruebas as $orden => [$nombre, $modalidad, $criterios]) {
            $prueba = Prueba::updateOrCreate(
                ['federacion_id' => $federacion->id, 'nombre' => $nombre],
                ['modalidad' => $modalidad, 'orden' => $orden + 1],
            );

            foreach ($criterios as $i => [$papel, $criterio, $permiteCero]) {
                $prueba->criterios()->updateOrCreate(
                    ['papel_juez' => $papel, 'nombre' => $criterio],
                    ['escala_id' => $escalaCompetencia?->id, 'permite_cero' => $permiteCero, 'orden' => $i + 1],
                );
            }
        }
    }
}
