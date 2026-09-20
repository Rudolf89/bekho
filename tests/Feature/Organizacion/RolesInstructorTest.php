<?php

use App\Livewire\Estudiantes\GestionEstudiantes;
use App\Livewire\Examenes\DetalleConvocatoria;
use App\Models\CargoRango;
use App\Models\Clase;
use App\Models\Convocatoria;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Matricula;
use App\Models\Persona;
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
    Tenant::set($this->bekho->id);
});

function usuarioRol(string $rol, ?int $grupoId, ?int $rangoId = null): User
{
    $exige2fa = in_array($rol, config('bekho.2fa_obligatorio_para', []), true);
    $user = User::factory()->create([
        'grupo_id' => $grupoId,
        'rango_id' => $rangoId,
        'two_factor_confirmed_at' => $exige2fa ? now() : null,
    ]);
    $user->assignRole($rol);

    return $user;
}

/**
 * Matrícula activa (alumno) con su persona, para las pruebas de visibilidad.
 */
function matriculaRol(int $grupoId, string $nombre, ?int $sedeId, string $etario): Matricula
{
    $persona = Persona::create(['nombres' => $nombre, 'fecha_nacimiento' => now()->subYears(10)]);

    return Matricula::withoutGlobalScopes()->create([
        'grupo_id' => $grupoId, 'persona_id' => $persona->id, 'sede_id' => $sedeId,
        'grupo_etario' => $etario, 'estado' => 'activa', 'fecha_ingreso' => now(),
    ]);
}

// --- Rol y rango son ejes independientes -------------------------------------

test('un administrativo sin rango gestiona alumnos pero no pagos', function () {
    $admin = usuarioRol('administrativo', $this->bekho->id);

    expect($admin->rango_id)->toBeNull();

    Tenant::olvidar();
    $this->actingAs($admin)->get(route('estudiantes.index'))->assertOk();
    $this->actingAs($admin)->get(route('inscripcion.crear'))->assertOk();
    $this->actingAs($admin)->get(route('pagos.index'))->assertForbidden();
});

test('un usuario con rango y sin rol operativo no entra a la gestión', function () {
    $maestro = CargoRango::create(['nombre' => 'Maestro', 'nivel' => 4]);

    // Tiene un rango marcial pero su rol de software es "alumno" (sin permisos de gestión).
    $user = usuarioRol('alumno', $this->bekho->id, $maestro->id);

    expect($user->rango_id)->toBe($maestro->id);

    Tenant::olvidar();
    $this->actingAs($user)->get(route('estudiantes.index'))->assertForbidden();
    $this->actingAs($user)->get(route('clases.index'))->assertForbidden();
    $this->actingAs($user)->get(route('pagos.index'))->assertForbidden();
});

// --- Instructor: ve solo los alumnos de sus clases ---------------------------

test('un instructor ve solo los alumnos de sus clases y no los de otra', function () {
    $sedeA = Sede::create(['grupo_id' => $this->bekho->id, 'nombre' => 'Sede A', 'activo' => true]);
    $sedeB = Sede::create(['grupo_id' => $this->bekho->id, 'nombre' => 'Sede B', 'activo' => true]);

    $instructor = usuarioRol('instructor', $this->bekho->id);

    // El instructor está asignado a una clase For Kids en la Sede A.
    $clase = Clase::create([
        'grupo_id' => $this->bekho->id, 'sede_id' => $sedeA->id, 'nombre' => 'Kids A',
        'grupo_etario' => 'for_kids', 'activo' => true,
    ]);
    $clase->sincronizarInstructores([$instructor->id => 'titular']);

    $suyo = matriculaRol($this->bekho->id, 'Alumno Suyo', $sedeA->id, 'for_kids');
    $otraSede = matriculaRol($this->bekho->id, 'Alumno Otra Sede', $sedeB->id, 'for_kids');
    $otroGrupo = matriculaRol($this->bekho->id, 'Alumno Otro Grupo', $sedeA->id, 'tigers');

    // Scope de consulta.
    $visibles = Matricula::visiblePara($instructor)->with('persona')->get()
        ->map(fn ($m) => $m->persona->nombreCompleto())->all();
    expect($visibles)->toBe(['Alumno Suyo']);

    // Policy por matrícula.
    expect($instructor->can('view', $suyo))->toBeTrue();
    expect($instructor->can('view', $otraSede))->toBeFalse();
    expect($instructor->can('view', $otroGrupo))->toBeFalse();

    // En la pantalla real solo aparece el suyo.
    Livewire::actingAs($instructor)->test(GestionEstudiantes::class)
        ->assertSee('Alumno Suyo')
        ->assertDontSee('Alumno Otra Sede')
        ->assertDontSee('Alumno Otro Grupo');
});

test('un instructor sin clases no ve ningún alumno', function () {
    $instructor = usuarioRol('instructor', $this->bekho->id);

    matriculaRol($this->bekho->id, 'Cualquiera', null, 'for_kids');

    expect(Matricula::visiblePara($instructor)->count())->toBe(0);
});

// --- Instructor: inscribe en exámenes sin aprobación -------------------------

test('un instructor puede inscribir a un alumno en un examen', function () {
    $instructor = usuarioRol('instructor', $this->bekho->id);
    $conv = Convocatoria::create(['grupo_id' => $this->bekho->id, 'nombre' => 'Examen',
        'fecha' => now()->addDays(5), 'estado' => 'programada']);
    $persona = Persona::create(['nombres' => 'Inscribible', 'fecha_nacimiento' => now()->subYears(10)]);
    $matricula = Matricula::create(['grupo_id' => $this->bekho->id, 'persona_id' => $persona->id,
        'grupo_etario' => 'for_kids', 'estado' => 'activa', 'fecha_ingreso' => now()]);

    Livewire::actingAs($instructor)->test(DetalleConvocatoria::class, ['convocatoria' => $conv])
        ->call('inscribir', $matricula->id);

    expect(Inscripcion::where('convocatoria_id', $conv->id)->where('matricula_id', $matricula->id)->exists())
        ->toBeTrue();
});

// --- Aislamiento entre grupos se mantiene ---------------------------------

test('un instructor de un grupo no ve alumnos de otra grupo', function () {
    $otra = Grupo::create(['nombre' => 'Otro Grupo', 'activo' => true]);

    $sede = Sede::create(['grupo_id' => $this->bekho->id, 'nombre' => 'Sede', 'activo' => true]);
    $instructor = usuarioRol('instructor', $this->bekho->id);
    $clase = Clase::create([
        'grupo_id' => $this->bekho->id, 'sede_id' => $sede->id, 'nombre' => 'Kids',
        'grupo_etario' => 'for_kids', 'activo' => true,
    ]);
    $clase->sincronizarInstructores([$instructor->id => 'titular']);

    // Alumno de la otra grupo (su sede es de otro grupo; aquí basta sin sede).
    matriculaRol($otra->id, 'Ajeno', null, 'for_kids');

    // El tenant del instructor filtra por su grupo: no aparece el ajeno.
    Tenant::set($this->bekho->id);
    expect(Matricula::visiblePara($instructor)->count())->toBe(0);
});
