<?php

use App\Enums\PapelJuez;
use App\Models\CategoriaCompetencia;
use App\Models\CriterioPrueba;
use App\Models\Prueba;
use Database\Seeders\CatalogosFederacionSeeder;
use Database\Seeders\CompetenciaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(CatalogosFederacionSeeder::class); // escalas de puntaje
    $this->seed(CompetenciaSeeder::class);
});

test('los catálogos de competencia son de la federación', function () {
    foreach (['grupos_edad', 'categorias_competencia', 'pruebas', 'tabla_libres'] as $tabla) {
        expect(Schema::hasColumn($tabla, 'federacion_id'))->toBeTrue()
            ->and(Schema::hasColumn($tabla, 'grupo_id'))->toBeFalse();
    }
});

test('el seeder crea las categorías y es idempotente', function () {
    $this->seed(CompetenciaSeeder::class);

    expect(CategoriaCompetencia::count())->toBe(2)
        ->and(CategoriaCompetencia::pluck('tipo')->all())->toContain('color', 'negro');
});

test('la prueba de formas tiene sus tres criterios por papel de juez', function () {
    $formas = Prueba::where('nombre', 'Formas tradicionales')->with('criterios')->first();

    expect($formas->modalidad)->toBe('formas')
        ->and($formas->criterios)->toHaveCount(3)
        ->and($formas->criterios->pluck('papel_juez')->map->value->all())->toBe(['a', 'central', 'b']);

    // Solo el juez central puede penalizar con 0.
    $central = $formas->criterios->firstWhere('papel_juez', PapelJuez::Central);
    expect($central->permite_cero)->toBeTrue()
        ->and($central->escala->nombre)->toBe('Competencia');

    $lateral = $formas->criterios->firstWhere('papel_juez', PapelJuez::A);
    expect($lateral->permite_cero)->toBeFalse();
});

test('las pruebas de armas y combate quedan sembradas', function () {
    expect(Prueba::where('modalidad', 'armas')->exists())->toBeTrue()
        ->and(Prueba::where('modalidad', 'combate')->exists())->toBeTrue();

    // Armas usa la fórmula de posiciones/memorización/tiempo.
    $armas = Prueba::where('modalidad', 'armas')->first();
    expect(CriterioPrueba::where('prueba_id', $armas->id)->count())->toBe(3);
});
