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
 * Todo se transcribe de la PLANILLA DE COMPETENCIA OFICIAL de la federación
 * (`EN BLANCO PRUEBA PLANILLAS DE COMPETENCIA`): los grupos de edad, las
 * categorías y la tabla de libres van con `verificado = true`, y las pruebas
 * llevan el nombre y los criterios con que la planilla rotula las columnas de
 * cada juez. Idempotente.
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

        // Los nombres de los criterios se transcriben de la planilla oficial
        // (encabezados de las columnas de cada juez).
        $pruebas = [
            ['Formula Tradicional', 'formas', [
                ['a', 'Patadas y Posiciones', false],
                ['central', 'General', true],
                ['b', 'Golpes y Defensas', false],
            ]],
            ['Armas Tradicionales', 'armas', [
                ['a', 'Posiciones y Golpes', false],
                ['central', 'Memorización, Transición, Apariencia, Actitud', true],
                ['b', 'Tiempo, Fluidez, Precisión, Consistencia', false],
            ]],
            ['Sparring', 'combate', []],
        ];

        foreach ($pruebas as $orden => [$nombre, $modalidad, $criterios]) {
            // Se busca por modalidad (estable) para poder corregir el nombre.
            $prueba = Prueba::updateOrCreate(
                ['federacion_id' => $federacion->id, 'modalidad' => $modalidad],
                ['nombre' => $nombre, 'orden' => $orden + 1],
            );

            foreach ($criterios as $i => [$papel, $criterio, $permiteCero]) {
                // Un criterio por papel de juez: la clave es el papel, así el
                // nombre se puede corregir sin duplicar ni perder los puntajes.
                $prueba->criterios()->updateOrCreate(
                    ['papel_juez' => $papel],
                    [
                        'nombre' => $criterio,
                        'escala_id' => $escalaCompetencia?->id,
                        'permite_cero' => $permiteCero,
                        'orden' => $i + 1,
                    ],
                );
            }
        }
    }
}
