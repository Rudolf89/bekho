<?php

use App\Enums\TipoBloque;
use App\Livewire\Planillas\Planificador;
use App\Models\Academia;
use App\Models\CategoriaCalentamiento;
use App\Models\Clase;
use App\Models\EjercicioCalentamiento;
use App\Models\Planilla;
use App\Models\Sede;
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

// ── Transversalidad (las planillas son contenido compartido) ────────────────

test('las planillas del planificador son transversales (se ven en cualquier academia)', function () {
    $otra = Academia::create(['nombre' => 'Otro Grupo', 'activo' => true]);

    // Las 9 planillas sembradas se ven con cualquier academia activa.
    Tenant::set($this->bekho->id);
    expect(Planilla::count())->toBe(9);

    Tenant::set($otra->id);
    expect(Planilla::count())->toBe(9);
});

// ── Interfaz: armar y guardar calentamiento (por clase) ─────────────────────

test('el instructor arma una rutina de calentamiento y se guarda en la clase', function () {
    $instructor = User::factory()->create(['academia_id' => $this->bekho->id]);
    $instructor->assignRole('instructor');

    $sede = Sede::create(['academia_id' => $this->bekho->id, 'nombre' => 'Central', 'activo' => true]);
    $clase = Clase::create([
        'academia_id' => $this->bekho->id, 'sede_id' => $sede->id, 'nombre' => 'Kids',
        'grupo_etario' => 'for_kids', 'dia_semana' => 1, 'hora_inicio' => '10:00', 'activo' => true,
    ]);
    $ejercicio = EjercicioCalentamiento::whereHas('categoria', fn ($q) => $q->where('clave', 'guardia'))->first();

    Livewire::actingAs($instructor)->test(Planificador::class)
        ->set('tab', 'warmup')
        ->set('claseId', (string) $clase->id)
        ->call('alternarEjercicio', $ejercicio->id)
        ->call('guardarCalentamiento');

    expect($clase->fresh()->calentamiento()->count())->toBe(1)
        ->and($clase->fresh()->calentamiento()->first()->id)->toBe($ejercicio->id);
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
