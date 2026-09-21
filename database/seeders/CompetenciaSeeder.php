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
     * Grupos de edad de la planilla, con su rótulo LITERAL y en su orden (la
     * planilla los lista por columnas). Tigers va SIN rango de edad: la planilla
     * no lo indica y no se inventa.
     *
     * Se busca por `orden` (la posición en la planilla, estable) y no por
     * nombre, para poder corregir el rótulo sin duplicar la fila ni dejar
     * huérfanas las planillas que ya la usan.
     */
    private function gruposEdad(Federacion $federacion): void
    {
        $grupos = [
            ['TIGERS', null, null],
            ['7 a 8 años', 7, 8],
            ['9 a 10 años', 9, 10],
            ['11 a 12 años', 11, 12],
            ['13 a 14 años', 13, 14],
            ['15 a 17 años', 15, 17],
            ['18 a 29 años', 18, 29],
            ['30 a 39 años', 30, 39],
            ['40 a 49 años', 40, 49],
            ['50 a 59 años', 50, 59],
        ];

        foreach ($grupos as $orden => [$nombre, $desde, $hasta]) {
            GrupoEdad::updateOrCreate(
                ['federacion_id' => $federacion->id, 'orden' => $orden + 1],
                [
                    'nombre' => $nombre,
                    'edad_desde' => $desde,
                    'edad_hasta' => $hasta,
                    'fuente' => self::FUENTE,
                    'verificado' => true,
                ],
            );
        }
    }

    /**
     * Categorías de la planilla, con su rótulo LITERAL y en su orden (la
     * planilla las lista por columnas): color hasta Rojo-Negro y negro desde
     * 1 BD en adelante.
     *
     * La planilla escribe "Naranjo", "Púrpura" y "Café" donde el Manual ATA y la
     * tabla `grados` usan "Naranja", "Morado" y "Marrón". Es la misma escala con
     * otro nombre: aquí manda la planilla y los grados NO se renombran.
     */
    private function categorias(Federacion $federacion): void
    {
        $color = ['Blanco', 'Naranjo', 'Amarillo', 'Camuflado', 'Verde', 'Púrpura', 'Azul', 'Café', 'Rojo', 'Rojo-Negro'];
        $negro = ['1 BD', '2 BD y 3 BD', '4 BD y 5 BD', 'Ctg. Maestros', 'Ctg. Especial'];

        $orden = 0;

        foreach ([['color', $color], ['negro', $negro]] as [$tipo, $nombres]) {
            foreach ($nombres as $nombre) {
                $orden++;
                // Igual que los grupos de edad: la clave es la posición en la
                // planilla, así el rótulo se corrige sin duplicar la categoría.
                CategoriaCompetencia::updateOrCreate(
                    ['federacion_id' => $federacion->id, 'orden' => $orden],
                    ['nombre' => $nombre, 'tipo' => $tipo, 'fuente' => self::FUENTE, 'verificado' => true],
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
