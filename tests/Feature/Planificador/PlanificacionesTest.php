<?php

use App\Enums\Cuadrante;
use App\Enums\TipoBloque;
use App\Models\Clase;
use App\Models\Grupo;
use App\Models\PlanificacionClase;
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
});

function usuarioPlanificacion(string $rol, ?int $grupoId): User
{
    $exige2fa = in_array($rol, config('bekho.2fa_obligatorio_para', []), true);
    $user = User::factory()->create([
        'grupo_id' => $grupoId,
        'two_factor_confirmed_at' => $exige2fa ? now() : null,
    ]);
    $user->assignRole($rol);

    return $user;
}

function nuevaPlanificacion(?int $grupoId = null, array $extra = []): PlanificacionClase
{
    // Las planificaciones son transversales: no llevan grupo_id ($grupoId se
    // ignora; el parámetro se conserva por compatibilidad de las llamadas).
    return PlanificacionClase::create(array_merge([
        'nombre' => 'Rutina',
        'grupo_etario' => 'for_kids',
        'nivel' => 'principiantes',
        'activo' => true,
    ], $extra));
}

// --- Permisos ----------------------------------------------------------------

test('un alumno no accede a la gestión de planificaciones', function () {
    $user = usuarioPlanificacion('alumno', $this->bekho->id);

    $this->actingAs($user)->get(route("planificaciones.index"))->assertForbidden();
});

test('un instructor sí gestiona planificaciones', function () {
    // El rol instructor tiene el permiso "gestionar planificaciones".
    $user = usuarioPlanificacion('instructor', $this->bekho->id);

    $this->actingAs($user)->get(route("planificaciones.index"))->assertOk();
});

// --- Transversalidad ---------------------------------------------------------

test('las planificaciones son transversales: se ven desde cualquier grupo', function () {
    $otra = Grupo::create(['nombre' => 'OTRA', 'activo' => true]);
    nuevaPlanificacion(extra: ['nombre' => 'Compartida A']);
    nuevaPlanificacion(extra: ['nombre' => 'Compartida B']);

    // Con cualquier grupo activo se ven todas (contenido compartido ATA).
    Tenant::set($this->bekho->id);
    expect(PlanificacionClase::count())->toBe(2);

    Tenant::set($otra->id);
    expect(PlanificacionClase::count())->toBe(2);
});

// --- Regla central: estructura de la planificación --------------------------------

test('una planificación nueva genera sus 9 bloques y 4 cuadrantes en orden', function () {
    Tenant::set($this->bekho->id);
    $planificacion = nuevaPlanificacion($this->bekho->id);
    $planificacion->generarEstructura();

    expect($planificacion->bloques()->count())->toBe(count(TipoBloque::cases()))->toBe(9);
    expect($planificacion->cuadrantes()->count())->toBe(count(Cuadrante::cases()))->toBe(4);

    // Primer bloque = Calentamiento; último = Anuncios/Premios.
    $tipos = $planificacion->bloques->pluck('tipo');
    expect($tipos->first())->toBe(TipoBloque::Calentamiento);
    expect($tipos->last())->toBe(TipoBloque::AnunciosPremios);
});

// --- Enlace clase → planificación -------------------------------------------------

test('una clase puede apuntar a su planificación', function () {
    Tenant::set($this->bekho->id);
    $sede = Sede::create(['grupo_id' => $this->bekho->id, 'nombre' => 'Central', 'activo' => true]);
    $planificacion = nuevaPlanificacion($this->bekho->id, ['nombre' => 'Rutina Kids']);

    $clase = Clase::create([
        'grupo_id' => $this->bekho->id, 'sede_id' => $sede->id, 'planificacion_clase_id' => $planificacion->id,
        'nombre' => 'Kids AM', 'grupo_etario' => 'for_kids',
        'activo' => true,
    ]);

    expect($clase->planificacion->id)->toBe($planificacion->id);
    expect($planificacion->clases()->count())->toBe(1);
});

// --- Render ------------------------------------------------------------------

test('la pantalla de editar planificación renderiza para un instructor', function () {
    Tenant::set($this->bekho->id);
    $planificacion = nuevaPlanificacion($this->bekho->id);
    $planificacion->generarEstructura();

    $user = usuarioPlanificacion('instructor', $this->bekho->id);

    Tenant::olvidar();
    $this->actingAs($user)->get(route("planificaciones.editar", $planificacion))->assertOk();
});

test('editar planificación renderiza con bloques sin tipo (actividad con nombre propio)', function () {
    Tenant::set($this->bekho->id);
    $planificacion = nuevaPlanificacion($this->bekho->id, ['grupo_etario' => 'tigers']);
    // Bloque con tipo nulo pero título propio (caso Tigers: "Juego de Golpes").
    $planificacion->bloques()->create(['tipo' => null, 'titulo' => '⚔️ Juego de Golpes', 'orden' => 1]);

    $user = usuarioPlanificacion('instructor', $this->bekho->id);

    Tenant::olvidar();
    $this->actingAs($user)->get(route("planificaciones.editar", $planificacion))
        ->assertOk()
        ->assertSee('Juego de Golpes');
});
