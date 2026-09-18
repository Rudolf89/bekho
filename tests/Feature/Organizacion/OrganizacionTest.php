<?php

use App\Livewire\Grupos\GestionGrupos;
use App\Livewire\Sedes\GestionSedes;
use App\Models\Estudiante;
use App\Models\Grupo;
use App\Models\Sede;
use App\Models\User;
use App\Support\Tenancy\Grupo as Tenant;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->bekho = Grupo::where('nombre', 'BEKHO Power Academy')->first();
});

function actorOrg(string $rol, ?int $grupoId): User
{
    $exige2fa = in_array($rol, config('bekho.2fa_obligatorio_para', []), true);
    $user = User::factory()->create([
        'grupo_id' => $grupoId,
        'two_factor_confirmed_at' => $exige2fa ? now() : null,
    ]);
    $user->assignRole($rol);

    return $user;
}

// --- Alcance del admin-plataforma -------------------------------------------------

test('el admin-plataforma ve las sedes de todos los grupos (aunque tenga una activa)', function () {
    $otra = Grupo::create(['nombre' => 'ATA Norte', 'activo' => true]);
    Sede::create(['grupo_id' => $this->bekho->id, 'nombre' => 'Sede BEKHO', 'activo' => true]);
    Sede::create(['grupo_id' => $otra->id, 'nombre' => 'Sede Norte', 'activo' => true]);

    // Super-admin: grupo activo = BEKHO pero SIN filtrar lecturas (ve todo).
    Tenant::set($this->bekho->id, filtraLecturas: false);
    Livewire::actingAs(actorOrg('admin-plataforma', null))->test(GestionSedes::class)
        ->assertSee('Sede BEKHO')
        ->assertSee('Sede Norte');

    // Maestro: grupo activo = BEKHO filtrando lecturas (solo la suya).
    Tenant::set($this->bekho->id);
    Livewire::actingAs(actorOrg('direccion', $this->bekho->id))->test(GestionSedes::class)
        ->assertSee('Sede BEKHO')
        ->assertDontSee('Sede Norte');
});

test('los conteos de grupos son globales, no del grupo activo', function () {
    $otra = Grupo::create(['nombre' => 'ATA Norte', 'activo' => true]);
    Sede::create(['grupo_id' => $otra->id, 'nombre' => 'Sede Norte', 'activo' => true]);

    Tenant::set($this->bekho->id, filtraLecturas: false);

    Livewire::actingAs(actorOrg('admin-plataforma', null))->test(GestionGrupos::class)
        ->assertViewHas('grupos', fn ($grupos) => $grupos
            ->firstWhere('nombre', 'ATA Norte')?->sedes_count === 1);
});

test('el alcance del admin-plataforma (no filtrar lecturas) aplica a todo modelo por grupo', function () {
    $otra = Grupo::create(['nombre' => 'ATA Norte', 'activo' => true]);
    Estudiante::create(['grupo_id' => $this->bekho->id, 'nombre' => 'Alumno BEKHO', 'grupo_etario' => 'for_kids', 'activo' => true]);
    Estudiante::create(['grupo_id' => $otra->id, 'nombre' => 'Alumno Norte', 'grupo_etario' => 'for_kids', 'activo' => true]);

    // Con grupo activo filtrando (maestro): solo ve la suya.
    Tenant::set($this->bekho->id);
    expect(Estudiante::count())->toBe(1);

    // Super-admin (no filtra lecturas): ve las de todos los grupos.
    Tenant::set($this->bekho->id, filtraLecturas: false);
    expect(Estudiante::count())->toBe(2);
});

test('en una petición real el admin-plataforma ve alumnos de otra grupo', function () {
    $otra = Grupo::create(['nombre' => 'ATA Norte', 'activo' => true]);
    Estudiante::create(['grupo_id' => $otra->id, 'nombre' => 'Alumno Otra Grupo', 'grupo_etario' => 'for_kids', 'activo' => true]);

    Tenant::olvidar();

    // La petición pasa por el middleware, que para el admin-plataforma no filtra.
    $this->actingAs(actorOrg('admin-plataforma', null))
        ->get(route('estudiantes.index'))
        ->assertOk()
        ->assertSee('Alumno Otra Grupo');
});

test('el admin-plataforma enfocado en un grupo solo ve los datos de ese grupo', function () {
    $otra = Grupo::create(['nombre' => 'ATA Norte', 'activo' => true]);
    Estudiante::create(['grupo_id' => $this->bekho->id, 'nombre' => 'Alumno BEKHO', 'grupo_etario' => 'for_kids', 'activo' => true]);
    Estudiante::create(['grupo_id' => $otra->id, 'nombre' => 'Alumno Norte', 'grupo_etario' => 'for_kids', 'activo' => true]);

    Tenant::olvidar();

    // Elige BEKHO en el selector (sesión): la vista se acota a ese grupo.
    $this->actingAs(actorOrg('admin-plataforma', null))
        ->withSession(['grupo_activa_id' => $this->bekho->id])
        ->get(route('estudiantes.index'))
        ->assertOk()
        ->assertSee('Alumno BEKHO')
        ->assertDontSee('Alumno Norte');
});

test('el admin-plataforma en "Todos los grupos" ve los datos de todas', function () {
    $otra = Grupo::create(['nombre' => 'ATA Norte', 'activo' => true]);
    Estudiante::create(['grupo_id' => $this->bekho->id, 'nombre' => 'Alumno BEKHO', 'grupo_etario' => 'for_kids', 'activo' => true]);
    Estudiante::create(['grupo_id' => $otra->id, 'nombre' => 'Alumno Norte', 'grupo_etario' => 'for_kids', 'activo' => true]);

    Tenant::olvidar();

    // Sin grupo elegido (Todas): ve las de todos los grupos.
    $this->actingAs(actorOrg('admin-plataforma', null))
        ->withSession(['grupo_activa_id' => null])
        ->get(route('estudiantes.index'))
        ->assertOk()
        ->assertSee('Alumno BEKHO')
        ->assertSee('Alumno Norte');
});

// --- Permisos ----------------------------------------------------------------

test('un maestro gestiona sedes pero no grupos', function () {
    $user = actorOrg('direccion', $this->bekho->id);

    Tenant::olvidar();
    $this->actingAs($user)->get(route('sedes.index'))->assertOk();
    $this->actingAs($user)->get(route('grupos.index'))->assertForbidden();
});

test('un instructor no gestiona sedes', function () {
    $user = actorOrg('instructor', $this->bekho->id);

    $this->actingAs($user)->get(route('sedes.index'))->assertForbidden();
});

test('el admin-plataforma gestiona grupos', function () {
    $user = actorOrg('admin-plataforma', null);

    $this->actingAs($user)->get(route('grupos.index'))->assertOk();
});

// --- Sedes: scope y creación -------------------------------------------------

test('las sedes se aíslan por grupo', function () {
    $otra = Grupo::create(['nombre' => 'OTRA', 'activo' => true]);
    Sede::create(['grupo_id' => $this->bekho->id, 'nombre' => 'De BEKHO', 'activo' => true]);
    Sede::create(['grupo_id' => $otra->id, 'nombre' => 'De OTRA', 'activo' => true]);

    Tenant::set($this->bekho->id);
    expect(Sede::count())->toBe(1);
    expect(Sede::first()->nombre)->toBe('De BEKHO');
});

test('un maestro crea una sede en su propia grupo', function () {
    $maestro = actorOrg('direccion', $this->bekho->id);

    Livewire::actingAs($maestro)->test(GestionSedes::class)
        ->call('nueva')
        ->set('nombre', 'Sede Nueva')
        ->set('comuna', 'Ñuñoa')
        ->call('guardar')
        ->assertHasNoErrors();

    $sede = Sede::sinGrupo()->where('nombre', 'Sede Nueva')->first();
    expect($sede)->not->toBeNull();
    expect($sede->grupo_id)->toBe($this->bekho->id);
});

// --- Grupos: creación -----------------------------------------------------

test('el admin-plataforma crea un grupo nueva', function () {
    $admin = actorOrg('admin-plataforma', null);

    Livewire::actingAs($admin)->test(GestionGrupos::class)
        ->call('nueva')
        ->set('nombre', 'Grupo POWER')
        ->set('email', 'power@bekho.cl')
        ->call('guardar')
        ->assertHasNoErrors();

    expect(Grupo::where('nombre', 'Grupo POWER')->exists())->toBeTrue();
});
