<?php

use App\Livewire\Estudiantes\VerMatricula;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\NotaMatricula;
use App\Models\Persona;
use App\Models\Sede;
use App\Models\Tutela;
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
    $this->sede = Sede::create(['grupo_id' => $this->bekho->id, 'nombre' => 'Central', 'activo' => true]);
    Tenant::set($this->bekho->id);

    $persona = Persona::create(['nombres' => 'Sofía', 'apellido_paterno' => 'Martínez', 'fecha_nacimiento' => now()->subYears(14)]);
    $this->matricula = Matricula::create([
        'grupo_id' => $this->bekho->id, 'persona_id' => $persona->id, 'sede_id' => $this->sede->id,
        'grupo_etario' => 'for_kids', 'estado' => 'activa', 'fecha_ingreso' => now(),
    ]);
});

afterEach(fn () => Tenant::olvidar());

function actorFicha(string $rol, ?int $grupoId): User
{
    $exige2fa = in_array($rol, config('bekho.2fa_obligatorio_para', []), true);
    $u = User::factory()->create(['grupo_id' => $grupoId, 'two_factor_confirmed_at' => $exige2fa ? now() : null]);
    $u->assignRole($rol);

    return $u;
}

test('la dirección ve la ficha del alumno', function () {
    Livewire::actingAs(actorFicha('direccion', $this->bekho->id))
        ->test(VerMatricula::class, ['matricula' => $this->matricula])
        ->assertOk()
        ->assertSee('Sofía')
        ->assertSee('Progreso al siguiente cinturón')
        ->assertSee('Estado de cuenta');
});

test('la dirección agrega una nota del instructor', function () {
    Livewire::actingAs(actorFicha('direccion', $this->bekho->id))
        ->test(VerMatricula::class, ['matricula' => $this->matricula])
        ->set('nuevaNota', 'Patada lateral muy sólida.')
        ->call('agregarNota')
        ->assertHasNoErrors();

    expect(NotaMatricula::where('matricula_id', $this->matricula->id)->where('cuerpo', 'Patada lateral muy sólida.')->exists())->toBeTrue();
});

test('el apoderado ve la ficha pero no puede agregar notas', function () {
    $apoPersona = Persona::create(['nombres' => 'Madre', 'fecha_nacimiento' => now()->subYears(40)]);
    $apoderado = User::factory()->create(['grupo_id' => $this->bekho->id, 'persona_id' => $apoPersona->id]);
    $apoderado->assignRole('apoderado');
    Tutela::create(['apoderado_persona_id' => $apoPersona->id, 'alumno_persona_id' => $this->matricula->persona_id, 'parentesco' => 'madre']);

    Livewire::actingAs($apoderado)
        ->test(VerMatricula::class, ['matricula' => $this->matricula])
        ->assertOk()
        ->assertViewHas('puedeEditar', false)
        ->set('nuevaNota', 'Intento indebido')
        ->call('agregarNota')
        ->assertStatus(403);

    expect(NotaMatricula::count())->toBe(0);
});

test('un instructor de otra sede no puede ver la ficha', function () {
    $instructor = actorFicha('instructor', $this->bekho->id); // sin clases en esta sede

    Livewire::actingAs($instructor)
        ->test(VerMatricula::class, ['matricula' => $this->matricula])
        ->assertStatus(403);
});
