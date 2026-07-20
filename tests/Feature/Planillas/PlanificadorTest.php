<?php

use App\Enums\TipoBloque;
use App\Livewire\Planillas\Planificador;
use App\Models\Academia;
use App\Models\CategoriaCalentamiento;
use App\Models\EjercicioCalentamiento;
use App\Models\Planilla;
use App\Models\User;
use App\Support\Tenancy\Academia as Tenant;
use Database\Seeders\PlanificadorSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    $this->seed(PlanificadorSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->bekho = Academia::where('nombre', 'BEKHO Power Academy')->first();
    Tenant::set($this->bekho->id);
});

// ── Biblioteca de calentamiento: filtro por grupo ───────────────────────────

test('la biblioteca de calentamiento filtra las categorías por grupo etario', function () {
    $tigers = CategoriaCalentamiento::paraGrupo('tigers')->pluck('clave')->all();
    expect($tigers)->toContain('guardia', 'punos', 'piernas')
        ->and($tigers)->not->toContain('combopunos') // Tigers no hace combinaciones de puños
        ->and($tigers)->not->toContain('cardio');     // ni cardio

    $kids = CategoriaCalentamiento::paraGrupo('for_kids')->pluck('clave')->all();
    expect($kids)->toContain('combopunos', 'combo')
        ->and($kids)->not->toContain('cardio');        // For Kids no hace cardio

    $adults = CategoriaCalentamiento::paraGrupo('jovenes_adultos')->pluck('clave')->all();
    expect($adults)->toContain('cardio');              // Adultos sí
});

// ── Planillas: el mismo bloque cambia según el grupo etario ─────────────────

test('Tigers y Jóvenes y Adultos del mismo nivel tienen detalles distintos en el mismo bloque', function () {
    $tigers = Planilla::where('grupo_etario', 'tigers')->where('nivel', 'intermedio')->first();
    $adultos = Planilla::where('grupo_etario', 'jovenes_adultos')->where('nivel', 'intermedio')->first();

    $roturaTigers = $tigers->bloques->firstWhere('tipo', TipoBloque::Roturas);
    $roturaAdultos = $adultos->bloques->firstWhere('tipo', TipoBloque::Roturas);

    expect($roturaTigers->contenido)->not->toBeNull()
        ->and($roturaAdultos->contenido)->not->toBeNull()
        // Tigers rompe con foam y ayuda; adultos usa tablillas reales.
        ->and($roturaTigers->contenido)->not->toBe($roturaAdultos->contenido)
        ->and($roturaTigers->contenido)->toContain('foam')
        ->and($roturaAdultos->contenido)->toContain('Tablillas reales');
});

test('cada grupo × nivel tiene su planilla con bloques', function () {
    expect(Planilla::count())->toBe(9); // 3 grupos × 3 niveles
    Planilla::each(fn ($p) => expect($p->bloques()->count())->toBe(8));
});

// ── Aislamiento por academia (planillas llevan academia_id) ─────────────────

test('las planillas del planificador se aíslan por academia', function () {
    $otra = Academia::create(['nombre' => 'Otro Grupo', 'activo' => true]);
    Planilla::create([
        'academia_id' => $otra->id, 'nombre' => 'Ajena', 'grupo_etario' => 'for_kids',
        'nivel' => 'principiantes', 'activo' => true,
    ]);

    Tenant::set($this->bekho->id);
    expect(Planilla::count())->toBe(9); // solo las de BEKHO, no la ajena

    Tenant::set($otra->id);
    expect(Planilla::count())->toBe(1);
});

// ── Interfaz: armar y guardar calentamiento ─────────────────────────────────

test('el instructor arma una rutina de calentamiento y se guarda en la planilla', function () {
    $instructor = User::factory()->create(['academia_id' => $this->bekho->id]);
    $instructor->assignRole('instructor');

    $planilla = Planilla::where('grupo_etario', 'for_kids')->where('nivel', 'principiantes')->first();
    $ejercicio = EjercicioCalentamiento::whereHas('categoria', fn ($q) => $q->where('clave', 'guardia'))->first();

    Livewire::actingAs($instructor)->test(Planificador::class)
        ->set('grupo', 'for_kids')
        ->set('nivel', 'principiantes')
        ->set('tab', 'warmup')
        ->call('alternarEjercicio', $ejercicio->id)
        ->call('guardarCalentamiento');

    expect($planilla->fresh()->calentamiento()->count())->toBe(1)
        ->and($planilla->fresh()->calentamiento()->first()->id)->toBe($ejercicio->id);
});

test('la vista del planificador muestra la rutina del grupo y nivel elegidos', function () {
    $instructor = User::factory()->create(['academia_id' => $this->bekho->id]);
    $instructor->assignRole('instructor');

    Livewire::actingAs($instructor)->test(Planificador::class)
        ->set('grupo', 'jovenes_adultos')
        ->set('nivel', 'avanzado')
        ->set('tab', 'planner')
        ->assertSee('Choong Jung 1') // fórmula del nivel avanzado
        ->assertSee('Combat Weapon + Sparring');
});

test('el planificador exige el permiso de gestionar planillas', function () {
    $instructor = User::factory()->create(['academia_id' => $this->bekho->id]);
    $instructor->assignRole('instructor');
    $apoderado = User::factory()->create(['academia_id' => $this->bekho->id]);
    $apoderado->assignRole('apoderado');

    Tenant::olvidar();
    $this->actingAs($instructor)->get(route('planificador.index'))->assertOk();
    $this->actingAs($apoderado)->get(route('planificador.index'))->assertForbidden();
});
