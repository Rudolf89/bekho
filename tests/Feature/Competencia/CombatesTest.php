<?php

use App\Models\CompetidorPlanilla;
use App\Models\PlanillaCompetencia;
use App\Models\Prueba;
use App\Services\ServicioPlanillaCompetencia;
use Database\Seeders\CatalogosFederacionSeeder;
use Database\Seeders\CompetenciaSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    $this->seed(CatalogosFederacionSeeder::class);
    $this->seed(CompetenciaSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->servicio = app(ServicioPlanillaCompetencia::class);
    $prueba = Prueba::where('modalidad', 'combate')->first() ?? Prueba::first();
    $this->planilla = PlanillaCompetencia::create(['prueba_id' => $prueba->id, 'estado' => 'borrador']);
    $this->a = CompetidorPlanilla::create(['planilla_id' => $this->planilla->id, 'orden' => 1, 'nombre' => 'A']);
    $this->b = CompetidorPlanilla::create(['planilla_id' => $this->planilla->id, 'orden' => 2, 'nombre' => 'B']);
});

test('registrar combates lleva el orden', function () {
    $c1 = $this->servicio->registrarCombate($this->planilla, $this->a, $this->b, ['ronda' => 'Semifinal']);
    $c2 = $this->servicio->registrarCombate($this->planilla, $this->a, null, ['tipo' => 'libre']);

    expect($c1->orden)->toBe(1)
        ->and($c2->orden)->toBe(2)
        ->and($c2->competidor_b_id)->toBeNull();
});

test('el ganador se resuelve por puntos entre los no descalificados', function () {
    $combate = $this->servicio->registrarCombate($this->planilla, $this->a, $this->b);
    $this->servicio->registrarMarca($combate, $this->a, puntos: 3);
    $this->servicio->registrarMarca($combate, $this->b, puntos: 1);

    $ganador = $this->servicio->definirGanador($combate);

    expect($ganador?->id)->toBe($this->a->id)
        ->and($combate->fresh()->ganador_id)->toBe($this->a->id);
});

test('un empate no define ganador automático', function () {
    $combate = $this->servicio->registrarCombate($this->planilla, $this->a, $this->b);
    $this->servicio->registrarMarca($combate, $this->a, puntos: 2);
    $this->servicio->registrarMarca($combate, $this->b, puntos: 2);

    expect($this->servicio->definirGanador($combate))->toBeNull();
});

test('un descalificado no gana aunque tenga más puntos', function () {
    $combate = $this->servicio->registrarCombate($this->planilla, $this->a, $this->b);
    $this->servicio->registrarMarca($combate, $this->a, puntos: 5, descalificado: true);
    $this->servicio->registrarMarca($combate, $this->b, puntos: 1);

    expect($this->servicio->definirGanador($combate)?->id)->toBe($this->b->id);
});

test('el recuento de medallas se calcula desde los resultados', function () {
    $this->servicio->registrarResultado($this->planilla, $this->a, 1);
    $this->servicio->registrarResultado($this->planilla, $this->b, 2);

    $recuento = $this->servicio->recalcularMedallas($this->planilla);

    expect($recuento->primer_lugar)->toBe(1)
        ->and($recuento->segundo_lugar)->toBe(1)
        ->and($recuento->tercer_lugar)->toBe(0)
        ->and($recuento->participacion)->toBe(2);
});
