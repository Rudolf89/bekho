<?php

use App\Enums\TipoAtributoTecnico;
use App\Models\Arma;
use App\Models\AtributoTecnico;
use App\Models\Ciclo;
use App\Models\CuadranteItem;
use App\Models\HabilidadVida;
use App\Models\Posicion;
use Database\Seeders\CuadrantesSeeder;
use Database\Seeders\ManualLegacySeeder;
use Database\Seeders\PlanificadorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ManualLegacySeeder::class);
});

test('los catálogos del manual se siembran verificados y con fuente', function () {
    expect(Posicion::count())->toBe(11)
        ->and(HabilidadVida::count())->toBe(6)
        ->and(Arma::count())->toBe(6)
        ->and(AtributoTecnico::deTipo(TipoAtributoTecnico::Atributo)->count())->toBe(10)
        ->and(AtributoTecnico::deTipo(TipoAtributoTecnico::CriterioForma)->count())->toBe(3);

    $habilidad = HabilidadVida::first();
    expect($habilidad->verificado)->toBeTrue()
        ->and($habilidad->fuente)->toBe(ManualLegacySeeder::FUENTE);
});

test('los criterios de conocimiento de forma se siembran sin definición (el manual no la da)', function () {
    AtributoTecnico::deTipo(TipoAtributoTecnico::CriterioForma)->get()
        ->each(fn (AtributoTecnico $c) => expect($c->definicion)->toBeNull());
});

test('los ciclos enlazan a la habilidad de vida del catálogo por FK', function () {
    $this->seed(PlanificadorSeeder::class);

    $ciclos = Ciclo::with('habilidadVida')->get();
    expect($ciclos)->toHaveCount(6);
    $ciclos->each(fn (Ciclo $c) => expect($c->habilidadVida)->not->toBeNull());

    expect(Ciclo::where('habilidad_vida', 'disciplina')->first()->habilidadVida->nombre)->toBe('Disciplina');
});

test('los Cuadrantes de Enseñanza quedan marcados como contenido verificado del manual', function () {
    $this->seed(CuadrantesSeeder::class);

    expect(CuadranteItem::count())->toBeGreaterThan(0)
        ->and(CuadranteItem::where('verificado', false)->count())->toBe(0)
        ->and(CuadranteItem::first()->fuente)->toBe(ManualLegacySeeder::FUENTE);
});
