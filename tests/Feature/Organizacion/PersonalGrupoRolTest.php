<?php

use App\Models\Grupo;
use App\Models\Persona;
use App\Models\PersonalGrupo;
use App\Models\PersonalGrupoRol;
use App\Models\Sede;
use App\Models\User;
use App\Support\Tenancy\Grupo as Tenant;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->bekho = Grupo::where('nombre', 'BEKHO Power Academy')->first();
    Tenant::set($this->bekho->id);
});

afterEach(fn () => Tenant::olvidar());

function personalDe(int $grupoId): PersonalGrupo
{
    $persona = Persona::create(['nombres' => 'Trabajador', 'fecha_nacimiento' => now()->subYears(30)]);

    return PersonalGrupo::create(['persona_id' => $persona->id, 'grupo_id' => $grupoId, 'activo' => true]);
}

test('existe el rol direccion-sede', function () {
    expect(Role::where('name', 'direccion-sede')->exists())->toBeTrue();
});

test('otorga un rol al personal a nivel de grupo (sin sede)', function () {
    $personal = personalDe($this->bekho->id);

    $personal->otorgarRol('direccion');

    expect($personal->roles()->count())->toBe(1)
        ->and($personal->roles()->first()->sede_id)->toBeNull()
        ->and($personal->roles()->first()->role->name)->toBe('direccion');
});

test('otorga un rol acotado a una sede', function () {
    $personal = personalDe($this->bekho->id);
    $sede = Sede::create(['grupo_id' => $this->bekho->id, 'nombre' => 'Norte', 'activo' => true]);

    $personal->otorgarRol('direccion-sede', $sede->id);

    $rol = $personal->roles()->first();
    expect($rol->sede_id)->toBe($sede->id)
        ->and($rol->role->name)->toBe('direccion-sede');
});

test('una persona puede tener varios roles en el mismo grupo', function () {
    $personal = personalDe($this->bekho->id);

    $personal->otorgarRol('instructor');
    $personal->otorgarRol('administrativo');

    expect($personal->roles()->count())->toBe(2);
});

test('otorgar el mismo rol y sede es idempotente', function () {
    $personal = personalDe($this->bekho->id);

    $personal->otorgarRol('direccion');
    $personal->otorgarRol('direccion');

    expect(PersonalGrupoRol::where('personal_grupo_id', $personal->id)->count())->toBe(1);
});

test('la siembra completa refleja los roles del personal', function () {
    // La siembra completa maneja su propio tenant; tras sembrar se fija el grupo
    // de Rodolfo para leer su personal (el aislamiento falla cerrado sin grupo).
    $this->seed();
    Tenant::set($this->bekho->id);

    // Cada personal con grupo tiene al menos un rol reflejado.
    expect(PersonalGrupoRol::count())->toBeGreaterThan(0);

    $rodolfo = User::where('email', 'rodolfo@bekho.cl')->first();
    $personal = PersonalGrupo::where('persona_id', $rodolfo->persona_id)->first();

    expect($personal->roles()->whereHas('role', fn ($q) => $q->where('name', 'direccion'))->exists())->toBeTrue();
});
