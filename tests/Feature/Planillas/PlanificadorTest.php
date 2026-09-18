<?php

use App\Enums\HabilidadVida;
use App\Enums\TipoBloque;
use App\Livewire\Planillas\Planificador;
use App\Models\CategoriaCalentamiento;
use App\Models\Ciclo;
use App\Models\Clase;
use App\Models\EjercicioCalentamiento;
use App\Models\Grupo;
use App\Models\LeccionVida;
use App\Models\Planilla;
use App\Models\Sede;
use App\Models\User;
use App\Support\Tenancy\Grupo as Tenant;
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
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->bekho = Grupo::where('nombre', 'BEKHO Power Academy')->first();
    Tenant::set($this->bekho->id);
});

// ── Backbone: ciclos (6 Habilidades de Vida) ────────────────────────────────

test('se siembran los 6 ciclos, uno por Habilidad para la Vida', function () {
    $ciclos = Ciclo::ordenados()->get();

    expect($ciclos)->toHaveCount(6)
        ->and($ciclos->first()->habilidad_vida)->toBe(HabilidadVida::Disciplina)
        ->and($ciclos->last()->habilidad_vida)->toBe(HabilidadVida::Honestidad);
});

test('la lección de vida cuelga de su ciclo y deriva la habilidad del ciclo', function () {
    $leccion = LeccionVida::with('ciclo')->where('semana', 7)->first();

    expect($leccion->ciclo)->not->toBeNull()
        ->and($leccion->semana)->toBe(7)
        // La habilidad se deriva del ciclo (Disciplina).
        ->and($leccion->habilidad)->toBe(HabilidadVida::Disciplina);
});

test('la lección de Comunicación (Semana 6) queda cargada en su ciclo', function () {
    $ciclo = Ciclo::where('habilidad_vida', HabilidadVida::Comunicacion->value)->first();
    $leccion = $ciclo->lecciones()->where('semana', 6)->first();

    expect($leccion)->not->toBeNull()
        ->and($leccion->habilidad)->toBe(HabilidadVida::Comunicacion)
        ->and($leccion->comienzo_frase)->toBe('Saludar con CONFIANZA')
        ->and($leccion->durante_frase)->toBe('Usar palabras ALENTADORAS')
        ->and($leccion->fin_frase)->toBe('Usa tus modales.')
        ->and($leccion->fin_texto)->toContain('por favor', 'gracias', 'de nada');
});

test('el ciclo Comunicación tiene sus 8 semanas de lección sembradas', function () {
    $ciclo = Ciclo::where('habilidad_vida', HabilidadVida::Comunicacion->value)->first();

    expect($ciclo->lecciones()->count())->toBe(8)
        ->and($ciclo->lecciones()->pluck('semana')->sort()->values()->all())->toBe([1, 2, 3, 4, 5, 6, 7, 8])
        ->and($ciclo->lecciones()->where('semana', 1)->first()->comienzo_frase)->toBe('La comunicación es lo que me conecta con el mundo')
        ->and($ciclo->lecciones()->where('semana', 8)->first()->fin_frase)->toBe('Los líderes ayudan sin que se les pregunte');
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

test('las planillas del planificador son transversales (se ven en cualquier grupo)', function () {
    $otra = Grupo::create(['nombre' => 'Otro Grupo', 'activo' => true]);

    // Las 9 planillas sembradas se ven con cualquier grupo activo.
    Tenant::set($this->bekho->id);
    expect(Planilla::count())->toBe(9);

    Tenant::set($otra->id);
    expect(Planilla::count())->toBe(9);
});

// ── Interfaz: armar y guardar calentamiento (por clase) ─────────────────────

test('el instructor arma una rutina de calentamiento y se guarda en la clase', function () {
    $instructor = User::factory()->create(['grupo_id' => $this->bekho->id]);
    $instructor->assignRole('instructor');

    $sede = Sede::create(['grupo_id' => $this->bekho->id, 'nombre' => 'Central', 'activo' => true]);
    $clase = Clase::create([
        'grupo_id' => $this->bekho->id, 'sede_id' => $sede->id, 'nombre' => 'Kids',
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
    $instructor = User::factory()->create(['grupo_id' => $this->bekho->id]);
    $instructor->assignRole('instructor');

    Livewire::actingAs($instructor)->test(Planificador::class)
        ->set('grupo', 'jovenes_adultos')
        ->set('nivel', 'avanzado')
        ->set('tab', 'planner')
        ->assertSee('Choong Jung 1') // fórmula del nivel avanzado
        ->assertSee('Combat Weapon + Sparring');
});

test('el planner muestra la rotación del ciclo y cambia por bloque de semanas', function () {
    $this->seed(PlannerCiclosSeeder::class);

    $instructor = User::factory()->create(['grupo_id' => $this->bekho->id]);
    $instructor->assignRole('instructor');
    $ciclo1 = Ciclo::ordenados()->first();

    // Ciclo 1 (Disciplina), semanas 1&2: los Kicks son White Belt.
    $comp = Livewire::actingAs($instructor)->test(Planificador::class)
        ->set('tab', 'planner')
        ->set('cicloId', $ciclo1->id)
        ->set('bloque', '1&2')
        ->assertSee('Rotación del ciclo')
        ->assertSee('White Belt');

    // Al pasar a semanas 3&4, la rotación cambia a Orange Belt.
    $comp->set('bloque', '3&4')
        ->assertSee('Orange Belt')
        ->assertDontSee('White Belt');
});

test('la lección de vida se acota al ciclo elegido', function () {
    $instructor = User::factory()->create(['grupo_id' => $this->bekho->id]);
    $instructor->assignRole('instructor');
    $ciclos = Ciclo::ordenados()->get();

    // Ciclo 1 (Disciplina) tiene la lección de la semana 7.
    $comp = Livewire::actingAs($instructor)->test(Planificador::class)
        ->set('tab', 'leccion')
        ->set('cicloId', $ciclos->first()->id)
        ->assertSee('Disciplina')
        ->assertSee('VISUALICE SUS OBJETIVOS');

    // Un ciclo sin lecciones sembradas muestra las semanas marcadas como
    // pendientes, no la lección de otro ciclo.
    $comp->set('cicloId', $ciclos->get(1)->id)
        ->assertSee('Lección pendiente de aportar')
        ->assertSee('0 de 8 semanas cargadas')
        ->assertDontSee('VISUALICE SUS OBJETIVOS');
});

test('las 8 semanas se muestran como casilleros (cargadas + pendientes)', function () {
    $instructor = User::factory()->create(['grupo_id' => $this->bekho->id]);
    $instructor->assignRole('instructor');
    $comunicacion = Ciclo::where('habilidad_vida', HabilidadVida::Comunicacion->value)->first();

    // Ciclo Comunicación: 8 casilleros de semana, las 8 cargadas.
    Livewire::actingAs($instructor)->test(Planificador::class)
        ->set('tab', 'leccion')
        ->set('cicloId', $comunicacion->id)
        ->assertSee('Semana 1')
        ->assertSee('Semana 8')
        ->assertSee('8 de 8 semanas cargadas');
});

test('el planificador exige el permiso de gestionar planillas', function () {
    $instructor = User::factory()->create(['grupo_id' => $this->bekho->id]);
    $instructor->assignRole('instructor');
    $apoderado = User::factory()->create(['grupo_id' => $this->bekho->id]);
    $apoderado->assignRole('apoderado');

    Tenant::olvidar();
    $this->actingAs($instructor)->get(route('planificador.index'))->assertOk();
    $this->actingAs($apoderado)->get(route('planificador.index'))->assertForbidden();
});
