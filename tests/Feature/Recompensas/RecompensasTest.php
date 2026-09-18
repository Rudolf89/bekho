<?php

use App\Enums\GrupoEtario;
use App\Enums\TipoRecompensa;
use App\Livewire\Recompensas\PanelRecompensas;
use App\Models\Clase;
use App\Models\Estudiante;
use App\Models\Grupo;
use App\Models\Recompensa;
use App\Models\Sede;
use App\Models\User;
use App\Support\Tenancy\Grupo as Tenant;
use Database\Seeders\RecompensasSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    $this->seed(RecompensasSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->bekho = Grupo::where('nombre', 'BEKHO Power Academy')->first();
    Tenant::set($this->bekho->id);
});

afterEach(fn () => Tenant::olvidar());

function alumnoDe(Grupo $a, GrupoEtario $grupo = GrupoEtario::ForKids): Estudiante
{
    return Estudiante::create([
        'grupo_id' => $a->id, 'nombre' => 'Alumno '.uniqid(),
        'grupo_etario' => $grupo->value, 'activo' => true,
    ]);
}

// --- Catálogo ----------------------------------------------------------------

test('el catálogo de recompensas cubre los tres sistemas', function () {
    expect(Recompensa::where('tipo', TipoRecompensa::FranjaConocimiento)->count())->toBe(7)
        ->and(Recompensa::where('tipo', TipoRecompensa::StarTag)->count())->toBe(1)
        ->and(Recompensa::where('tipo', TipoRecompensa::Coleccionable)->count())->toBe(6);

    // Los coleccionables aplican a todos los grupos (sin grupo_etario).
    expect(Recompensa::where('tipo', TipoRecompensa::Coleccionable)->whereNotNull('grupo_etario')->count())->toBe(0);
});

test('el catálogo es transversal (compartido, sin grupo)', function () {
    $otra = Grupo::create(['nombre' => 'OTRA', 'activo' => true]);

    Tenant::set($this->bekho->id);
    $a = Recompensa::count();
    Tenant::set($otra->id);
    $b = Recompensa::count();

    expect($a)->toBe(14)->and($b)->toBe(14);
});

// --- Otorgar / quitar --------------------------------------------------------

test('el instructor otorga una recompensa a un alumno de sus clases', function () {
    $sede = Sede::create(['grupo_id' => $this->bekho->id, 'nombre' => 'Central', 'activo' => true]);
    $instructor = User::factory()->create(['grupo_id' => $this->bekho->id]);
    $instructor->assignRole('instructor');
    $clase = Clase::create([
        'grupo_id' => $this->bekho->id, 'sede_id' => $sede->id, 'nombre' => 'Kids', 'grupo_etario' => 'for_kids',
        'dia_semana' => 1, 'hora_inicio' => '10:00', 'activo' => true,
    ]);
    $clase->instructores()->attach($instructor->id, ['papel' => 'titular']);

    $alumno = alumnoDe($this->bekho);
    $alumno->update(['sede_id' => $sede->id]);

    $coleccionable = Recompensa::where('tipo', TipoRecompensa::Coleccionable)->first();

    Livewire::actingAs($instructor)->test(PanelRecompensas::class)
        ->set('estudianteId', $alumno->id)
        ->call('otorgar', $coleccionable->id);

    expect($alumno->logros()->where('recompensa_id', $coleccionable->id)->count())->toBe(1);
});

test('una recompensa no repetible solo se gana una vez; Star Tag se acumula', function () {
    $instructor = User::factory()->create(['grupo_id' => $this->bekho->id]);
    $instructor->assignRole('direccion'); // ve todos los alumnos
    $instructor->forceFill(['two_factor_confirmed_at' => now()])->save();

    $alumno = alumnoDe($this->bekho, GrupoEtario::Tigers);
    $coleccionable = Recompensa::where('tipo', TipoRecompensa::Coleccionable)->first();
    $estrella = Recompensa::where('tipo', TipoRecompensa::StarTag)->first();

    $panel = Livewire::actingAs($instructor)->test(PanelRecompensas::class)
        ->set('estudianteId', $alumno->id)
        ->call('otorgar', $coleccionable->id)
        ->call('otorgar', $coleccionable->id) // segundo intento: no duplica
        ->call('otorgar', $estrella->id)
        ->call('otorgar', $estrella->id);      // se acumula

    expect($alumno->logros()->where('recompensa_id', $coleccionable->id)->count())->toBe(1)
        ->and($alumno->logros()->where('recompensa_id', $estrella->id)->count())->toBe(2);

    // Quitar resta uno.
    $panel->call('quitar', $estrella->id);
    expect($alumno->logros()->where('recompensa_id', $estrella->id)->count())->toBe(1);
});

// --- Aplicabilidad por grupo -------------------------------------------------

test('el panel solo ofrece recompensas aplicables al grupo del alumno', function () {
    $direccion = User::factory()->create(['grupo_id' => $this->bekho->id, 'two_factor_confirmed_at' => now()]);
    $direccion->assignRole('direccion');

    $tiger = alumnoDe($this->bekho, GrupoEtario::Tigers);

    // Un alumno Tigers ve Star Tag y coleccionables, pero NO las franjas de For Kids.
    Livewire::actingAs($direccion)->test(PanelRecompensas::class)
        ->set('estudianteId', $tiger->id)
        ->assertSee('Estrella Tigre')
        ->assertSee('Coleccionable: Disciplina')
        ->assertDontSee('Franja Amarilla');
});

// --- Permisos ----------------------------------------------------------------

test('el panel de recompensas exige el permiso', function () {
    $instructor = User::factory()->create(['grupo_id' => $this->bekho->id]);
    $instructor->assignRole('instructor');
    $apoderado = User::factory()->create(['grupo_id' => $this->bekho->id]);
    $apoderado->assignRole('apoderado');

    Tenant::olvidar();
    $this->actingAs($instructor)->get(route('recompensas.index'))->assertOk();
    $this->actingAs($apoderado)->get(route('recompensas.index'))->assertForbidden();
});
