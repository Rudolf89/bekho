<?php

use App\Models\CategoriaCompetencia;
use App\Models\GrupoEdad;
use App\Models\TablaLibre;
use Database\Seeders\CompetenciaSeeder;
use Database\Seeders\FederacionesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(FederacionesSeeder::class);
    $this->seed(CompetenciaSeeder::class);
});

test('los grupos de edad salen de la planilla oficial, en su orden', function () {
    $grupos = GrupoEdad::ordenados()->get();

    expect($grupos)->toHaveCount(10)
        // Rótulos LITERALES del xlsx: la hoja impresa debe verse igual.
        ->and($grupos->pluck('nombre')->all())->toBe([
            'TIGERS', '7 a 8 años', '9 a 10 años', '11 a 12 años', '13 a 14 años',
            '15 a 17 años', '18 a 29 años', '30 a 39 años', '40 a 49 años', '50 a 59 años',
        ])
        ->and($grupos->pluck('orden')->all())->toBe(range(1, 10));
});

test('Tigers queda sin rango de edad porque la planilla no lo indica', function () {
    $tigers = GrupoEdad::where('nombre', 'TIGERS')->first();
    $adultos = GrupoEdad::where('nombre', '18 a 29 años')->first();

    expect($tigers->edad_desde)->toBeNull()
        ->and($tigers->edad_hasta)->toBeNull()
        ->and($adultos->edad_desde)->toBe(18)
        ->and($adultos->edad_hasta)->toBe(29);
});

test('las categorías salen de la planilla, en su orden y con su tipo', function () {
    $categorias = CategoriaCompetencia::ordenados()->get();

    expect($categorias)->toHaveCount(15)
        ->and($categorias->pluck('nombre')->all())->toBe([
            'Blanco', 'Naranjo', 'Amarillo', 'Camuflado', 'Verde', 'Púrpura',
            'Azul', 'Café', 'Rojo', 'Rojo-Negro',
            '1 BD', '2 BD y 3 BD', '4 BD y 5 BD', 'Ctg. Maestros', 'Ctg. Especial',
        ]);

    // Color hasta Rojo-Negro; negro desde 1 BD en adelante.
    expect($categorias->take(10)->pluck('tipo')->unique()->all())->toBe(['color'])
        ->and($categorias->slice(10)->pluck('tipo')->unique()->values()->all())->toBe(['negro']);
});

test('la planilla manda sobre el nombre del color: Naranjo, Púrpura y Café', function () {
    // El Manual y la tabla `grados` usan Naranja / Morado / Marrón: no se tocan.
    expect(CategoriaCompetencia::whereIn('nombre', ['Naranjo', 'Púrpura', 'Café'])->count())->toBe(3)
        ->and(CategoriaCompetencia::whereIn('nombre', ['Naranja', 'Morado', 'Marrón'])->count())->toBe(0);
});

test('la tabla de libres transcribe los 15 pares de la planilla', function () {
    $tabla = TablaLibre::orderBy('competidores')->get()->pluck('libres', 'competidores')->all();

    expect($tabla)->toBe([
        2 => 0, 3 => 1, 4 => 0, 5 => 3, 6 => 2, 7 => 1, 8 => 0,
        9 => 7, 10 => 6, 11 => 5, 12 => 4, 13 => 3, 14 => 2, 15 => 1, 16 => 0,
    ]);
});

test('los tres catálogos quedan marcados con la fuente y verificados', function () {
    foreach ([GrupoEdad::class, CategoriaCompetencia::class, TablaLibre::class] as $modelo) {
        expect($modelo::where('verificado', false)->count())->toBe(0)
            ->and($modelo::where('fuente', '!=', 'Planilla de competencia oficial BEKHO')->count())->toBe(0);
    }
});

test('el seeder es idempotente y retira las categorías genéricas viejas', function () {
    // Las que se sembraban antes de tener la planilla real.
    CategoriaCompetencia::create([
        'federacion_id' => GrupoEdad::first()->federacion_id,
        'nombre' => 'Color', 'tipo' => 'color', 'orden' => 99,
    ]);

    $this->seed(CompetenciaSeeder::class);

    expect(CategoriaCompetencia::count())->toBe(15)
        ->and(GrupoEdad::count())->toBe(10)
        ->and(TablaLibre::count())->toBe(15);
});

test('los rótulos viejos se renombran en la misma fila, sin duplicar', function () {
    // La categoría se busca por su posición en la planilla, así que corregir el
    // rótulo no crea una fila nueva ni deja huérfanas las planillas que la usan.
    $maestros = CategoriaCompetencia::where('nombre', 'Ctg. Maestros')->first();
    $maestros->update(['nombre' => 'Categoría Maestros']);

    $this->seed(CompetenciaSeeder::class);

    expect(CategoriaCompetencia::count())->toBe(15)
        ->and($maestros->fresh()->nombre)->toBe('Ctg. Maestros');
});
