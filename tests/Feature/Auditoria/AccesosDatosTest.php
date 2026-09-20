<?php

use App\Enums\ResultadoBusqueda;
use App\Models\AccesoDato;
use App\Models\DocumentoPersona;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Persona;
use App\Models\PersonalGrupo;
use App\Models\Sede;
use App\Models\User;
use App\Services\BuscadorPersonas;
use App\Support\Tenancy\Grupo as Tenant;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->bekho = Grupo::where('nombre', 'BEKHO Power Academy')->first();
    $this->sede = Sede::create(['grupo_id' => $this->bekho->id, 'nombre' => 'Central', 'activo' => true]);
    $this->user = User::factory()->create(['grupo_id' => $this->bekho->id]);
});

afterEach(fn () => Tenant::olvidar());

function personaConRut(string $rut): Persona
{
    $persona = Persona::create(['nombres' => 'Buscada', 'fecha_nacimiento' => now()->subYears(20)]);
    DocumentoPersona::create(['persona_id' => $persona->id, 'tipo' => 'rut', 'numero' => $rut, 'pais' => 'CL', 'principal' => true]);

    return $persona;
}

test('buscar un documento inexistente registra el acceso como no existe', function () {
    $r = app(BuscadorPersonas::class)->buscar('11111111-1', $this->user, $this->bekho->id);

    expect($r['resultado'])->toBe(ResultadoBusqueda::NoExiste)
        ->and($r['persona'])->toBeNull()
        ->and(AccesoDato::where('documento_consultado', '11111111-1')->first()->resultado)
        ->toBe(ResultadoBusqueda::NoExiste);
});

test('una persona con matrícula activa se clasifica como existe con matrícula', function () {
    $persona = personaConRut('12345678-5');
    Matricula::withoutGlobalScopes()->create([
        'grupo_id' => $this->bekho->id, 'persona_id' => $persona->id, 'sede_id' => $this->sede->id,
        'grupo_etario' => 'jovenes_adultos', 'estado' => 'activa', 'fecha_ingreso' => now(),
    ]);

    // Busca con el RUT sin normalizar (con puntos): igual lo encuentra.
    $r = app(BuscadorPersonas::class)->buscar('12.345.678-5', $this->user, $this->bekho->id);

    expect($r['resultado'])->toBe(ResultadoBusqueda::ExisteConMatricula)
        ->and($r['persona']->id)->toBe($persona->id);
});

test('una persona sin matrícula pero que es personal se clasifica como personal', function () {
    $persona = personaConRut('7654321-6');
    PersonalGrupo::withoutGlobalScopes()->create(['grupo_id' => $this->bekho->id, 'persona_id' => $persona->id, 'activo' => true]);

    expect(app(BuscadorPersonas::class)->buscar('7654321-6')['resultado'])
        ->toBe(ResultadoBusqueda::ExistePersonal);
});

test('una persona sin matrícula ni vínculo se clasifica como existe sin matrícula', function () {
    personaConRut('9999999-9');

    expect(app(BuscadorPersonas::class)->buscar('9999999-9')['resultado'])
        ->toBe(ResultadoBusqueda::ExisteSinMatricula);
});

test('una persona eliminada se clasifica como eliminada (restaurable)', function () {
    $persona = personaConRut('5555555-5');
    $persona->delete();

    expect(app(BuscadorPersonas::class)->buscar('5555555-5')['resultado'])
        ->toBe(ResultadoBusqueda::Eliminada);
});

test('el acceso queda auditado con el usuario y el grupo', function () {
    app(BuscadorPersonas::class)->buscar('1111111-1', $this->user, $this->bekho->id);

    $acceso = AccesoDato::first();
    expect($acceso->user_id)->toBe($this->user->id)
        ->and($acceso->grupo_id)->toBe($this->bekho->id);
});
