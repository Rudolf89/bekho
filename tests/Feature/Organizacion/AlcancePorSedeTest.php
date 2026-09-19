<?php

use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Persona;
use App\Models\PersonalGrupo;
use App\Models\Sede;
use App\Models\User;
use App\Support\Tenancy\Grupo as Tenant;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->bekho = Grupo::where('nombre', 'BEKHO Power Academy')->first();
    $this->sedeA = Sede::create(['grupo_id' => $this->bekho->id, 'nombre' => 'Sede A', 'activo' => true]);
    $this->sedeB = Sede::create(['grupo_id' => $this->bekho->id, 'nombre' => 'Sede B', 'activo' => true]);
    Tenant::set($this->bekho->id);
});

afterEach(fn () => Tenant::olvidar());

function matriculaEnSede(int $grupoId, int $sedeId, string $nombre): Matricula
{
    $persona = Persona::create(['nombres' => $nombre, 'fecha_nacimiento' => now()->subYears(10)]);

    return Matricula::create([
        'grupo_id' => $grupoId, 'persona_id' => $persona->id, 'sede_id' => $sedeId,
        'grupo_etario' => 'for_kids', 'estado' => 'activa', 'fecha_ingreso' => now(),
    ]);
}

/** Usuario direccion-sede acotado a una sede vía personal_grupo_rol. */
function direccionSede(int $grupoId, int $sedeId): User
{
    $persona = Persona::create(['nombres' => 'Jefe Sede', 'fecha_nacimiento' => now()->subYears(35)]);
    $user = User::factory()->create(['grupo_id' => $grupoId, 'persona_id' => $persona->id]);
    $user->assignRole('direccion-sede');

    $personal = PersonalGrupo::create(['persona_id' => $persona->id, 'grupo_id' => $grupoId, 'activo' => true]);
    $personal->otorgarRol('direccion-sede', $sedeId);

    return $user;
}

test('un direccion-sede solo ve las matrículas de su sede', function () {
    $matA = matriculaEnSede($this->bekho->id, $this->sedeA->id, 'De Sede A');
    $matB = matriculaEnSede($this->bekho->id, $this->sedeB->id, 'De Sede B');

    $user = direccionSede($this->bekho->id, $this->sedeA->id);

    $visibles = Matricula::visiblePara($user)->pluck('id')->all();

    expect($visibles)->toContain($matA->id)
        ->and($visibles)->not->toContain($matB->id)
        ->and($user->sedesRestringidas())->toBe([$this->sedeA->id]);
});

test('la policy respeta el alcance por sede', function () {
    $matA = matriculaEnSede($this->bekho->id, $this->sedeA->id, 'De Sede A');
    $matB = matriculaEnSede($this->bekho->id, $this->sedeB->id, 'De Sede B');

    $user = direccionSede($this->bekho->id, $this->sedeA->id);

    expect($user->can('view', $matA))->toBeTrue()
        ->and($user->can('view', $matB))->toBeFalse()
        ->and($user->can('update', $matB))->toBeFalse();
});

test('un rol a nivel de grupo (sin sede) no queda restringido', function () {
    matriculaEnSede($this->bekho->id, $this->sedeA->id, 'De Sede A');
    matriculaEnSede($this->bekho->id, $this->sedeB->id, 'De Sede B');

    // direccion a nivel de grupo: personal_grupo_rol con sede nula.
    $persona = Persona::create(['nombres' => 'Directora', 'fecha_nacimiento' => now()->subYears(40)]);
    $user = User::factory()->create(['grupo_id' => $this->bekho->id, 'persona_id' => $persona->id, 'two_factor_confirmed_at' => now()]);
    $user->assignRole('direccion');
    $personal = PersonalGrupo::create(['persona_id' => $persona->id, 'grupo_id' => $this->bekho->id, 'activo' => true]);
    $personal->otorgarRol('direccion');

    expect($user->sedesRestringidas())->toBeNull()
        ->and(Matricula::visiblePara($user)->count())->toBe(2);
});
