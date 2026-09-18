<?php

use App\Enums\FilaPlannerCiclo;
use App\Enums\HabilidadVida;
use App\Livewire\Planillas\PlanCiclos;
use App\Models\Ciclo;
use App\Models\Grupo;
use App\Models\PlannerCiclo;
use App\Models\User;
use Database\Seeders\PlanificadorSeeder;
use Database\Seeders\PlannerCiclosSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    $this->seed(PlanificadorSeeder::class);
    $this->seed(PlannerCiclosSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->bekho = Grupo::where('nombre', 'BEKHO Power Academy')->first();
});

test('se siembran las grillas de los 6 ciclos (6 filas × 4 bloques)', function () {
    expect(Ciclo::count())->toBe(6)
        ->and(PlannerCiclo::count())->toBe(6 * 6 * 4);

    $disciplina = Ciclo::where('habilidad_vida', HabilidadVida::Disciplina)->first();
    expect($disciplina->planner()->count())->toBe(6 * 4);
});

test('el planner es transversal (catálogo compartido, sin grupo)', function () {
    // La tabla no tiene grupo_id: mismas filas con o sin tenant.
    expect(PlannerCiclo::count())->toBeGreaterThan(0)
        ->and(PlannerCiclo::first()->getAttributes())->not->toHaveKey('grupo_id');
});

test('cada celda cuelga de un ciclo con fila y bloque válidos', function () {
    $celda = PlannerCiclo::first();

    expect($celda->fila)->toBeInstanceOf(FilaPlannerCiclo::class)
        ->and(FilaPlannerCiclo::bloques())->toContain($celda->bloque)
        ->and($celda->ciclo)->toBeInstanceOf(Ciclo::class);
});

test('la página muestra la grilla del ciclo elegido y su lección', function () {
    $instructor = User::factory()->create(['grupo_id' => $this->bekho->id]);
    $instructor->assignRole('instructor');

    $disciplina = Ciclo::where('habilidad_vida', HabilidadVida::Disciplina)->first();

    Livewire::actingAs($instructor)->test(PlanCiclos::class)
        ->set('cicloId', $disciplina->id)
        ->assertSee('Disciplina')
        ->assertSee('Warm-Up')
        ->assertSee('White Belt'); // patadas 1&2 del ciclo Disciplina
});

test('la página de ciclos exige el permiso de gestionar planillas', function () {
    $instructor = User::factory()->create(['grupo_id' => $this->bekho->id]);
    $instructor->assignRole('instructor');
    $apoderado = User::factory()->create(['grupo_id' => $this->bekho->id]);
    $apoderado->assignRole('apoderado');

    $this->actingAs($instructor)->get(route('ciclos.index'))->assertOk();
    $this->actingAs($apoderado)->get(route('ciclos.index'))->assertForbidden();
});
