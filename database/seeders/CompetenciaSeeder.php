<?php

namespace Database\Seeders;

use App\Models\CategoriaCompetencia;
use App\Models\EscalaPuntaje;
use App\Models\Federacion;
use App\Models\GrupoEdad;
use App\Models\Prueba;
use App\Models\TablaLibre;
use Illuminate\Database\Seeder;

/**
 * Catálogos de competencia de la federación: grupos de edad, categorías, tabla
 * de libres y las pruebas con sus criterios de evaluación.
 *
 * Los grupos de edad, las categorías y la tabla de libres se transcriben de la
 * PLANILLA DE COMPETENCIA OFICIAL de la federación (`verificado = true`). Las
 * pruebas y sus criterios siguen viniendo del documento de decisiones.
 * Idempotente.
 */
class CompetenciaSeeder extends Seeder
{
    private const FUENTE = 'Planilla de competencia oficial BEKHO';

    public function run(): void
    {
        $federacion = Federacion::firstOrCreate(
            ['nombre' => 'BEKHO'],
            ['razon_social' => 'BEKHO Martial Arts', 'pais' => 'Chile', 'moneda' => 'CLP', 'activo' => true],
        );

        $this->gruposEdad($federacion);
        $this->categorias($federacion);
        $this->tablaLibres($federacion);
        $this->pruebas($federacion);
    }

    /**
     * Grupos de edad de la planilla, en su orden. Tigers va SIN rango de edad:
     * la planilla no lo indica y no se inventa.
     */
    private function gruposEdad(Federacion $federacion): void
    {
        $grupos = [
            ['Tigers', null, null],
            ['7 a 8', 7, 8],
            ['9 a 10', 9, 10],
            ['11 a 12', 11, 12],
            ['13 a 14', 13, 14],
            ['15 a 17', 15, 17],
            ['18 a 29', 18, 29],
            ['30 a 39', 30, 39],
            ['40 a 49', 40, 49],
            ['50 a 59', 50, 59],
        ];

        foreach ($grupos as $orden => [$nombre, $desde, $hasta]) {
            GrupoEdad::updateOrCreate(
                ['federacion_id' => $federacion->id, 'nombre' => $nombre],
                [
                    'edad_desde' => $desde,
                    'edad_hasta' => $hasta,
                    'orden' => $orden + 1,
                    'fuente' => self::FUENTE,
                    'verificado' => true,
                ],
            );
        }
    }

    /**
     * Categorías de la planilla, en su orden: color hasta Rojo-Negro y negro
     * desde 1 BD en adelante.
     *
     * La planilla escribe "Naranjo", "Púrpura" y "Café" donde el Manual ATA y la
     * tabla `grados` usan "Naranja", "Morado" y "Marrón". Es la misma escala con
     * otro nombre: aquí manda la planilla y los grados NO se renombran.
     */
    private function categorias(Federacion $federacion): void
    {
        $color = ['Blanco', 'Naranjo', 'Amarillo', 'Camuflado', 'Verde', 'Púrpura', 'Azul', 'Café', 'Rojo', 'Rojo-Negro'];
        $negro = ['1 BD', '2 BD y 3 BD', '4 BD y 5 BD', 'Categoría Maestros', 'Categoría Especial'];

        $orden = 0;

        foreach ([['color', $color], ['negro', $negro]] as [$tipo, $nombres]) {
            foreach ($nombres as $nombre) {
                $orden++;
                CategoriaCompetencia::updateOrCreate(
                    ['federacion_id' => $federacion->id, 'nombre' => $nombre],
                    ['tipo' => $tipo, 'orden' => $orden, 'fuente' => self::FUENTE, 'verificado' => true],
                );
            }
        }

        // Las categorías genéricas "Color"/"Negro" que se sembraban antes ya no
        // existen en la planilla: se retiran si ninguna planilla las usa.
        CategoriaCompetencia::where('federacion_id', $federacion->id)
            ->whereNotIn('nombre', [...$color, ...$negro])
            ->whereDoesntHave('planillas')
            ->delete();
    }

    /**
     * Tabla de libres de la planilla: cuántos libres corresponden según la
     * cantidad de competidores (de 2 a 16).
     */
    private function tablaLibres(Federacion $federacion): void
    {
        $libresPorCompetidores = [
            2 => 0, 3 => 1, 4 => 0, 5 => 3, 6 => 2, 7 => 1, 8 => 0,
            9 => 7, 10 => 6, 11 => 5, 12 => 4, 13 => 3, 14 => 2, 15 => 1, 16 => 0,
        ];

        foreach ($libresPorCompetidores as $competidores => $libres) {
            TablaLibre::updateOrCreate(
                ['federacion_id' => $federacion->id, 'competidores' => $competidores],
                ['libres' => $libres, 'fuente' => self::FUENTE, 'verificado' => true],
            );
        }
    }

    /**
     * Pruebas y sus criterios (papel de juez, `permite_cero` solo en el central).
     */
    private function pruebas(Federacion $federacion): void
    {
        $escalaCompetencia = EscalaPuntaje::where('federacion_id', $federacion->id)
            ->where('nombre', 'Competencia')->first();

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
