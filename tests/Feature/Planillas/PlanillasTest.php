<?php

use App\Enums\Cuadrante;
use App\Enums\TipoBloque;
use App\Models\Academia;
use App\Models\Clase;
use App\Models\Planilla;
use App\Models\Sede;
use App\Models\User;
use App\Support\Tenancy\Academia as Tenant;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->bekho = Academia::where('nombre', 'BEKHO Power Academy')->first();
});

function usuarioPlanilla(string $rol, ?int $academiaId): User
{
    $exige2fa = in_array($rol, config('bekho.2fa_obligatorio_para', []), true);
    $user = User::factory()->create([
        'academia_id' => $academiaId,
        'two_factor_confirmed_at' => $exige2fa ? now() : null,
    ]);
    $user->assignRole($rol);

    return $user;
}

function nuevaPlanilla(?int $academiaId = null, array $extra = []): Planilla
{
    // Las planillas son transversales: no llevan academia_id ($academiaId se
    // ignora; el parámetro se conserva por compatibilidad de las llamadas).
    return Planilla::create(array_merge([
        'nombre' => 'Rutina',
        'grupo_etario' => 'for_kids',
        'nivel' => 'principiantes',
        'activo' => true,
    ], $extra));
}

// --- Permisos ----------------------------------------------------------------

test('un alumno no accede a la gestión de planillas', function () {
    $user = usuarioPlanilla('alumno', $this->bekho->id);

    $this->actingAs($user)->get(route('planillas.index'))->assertForbidden();
});

test('un instructor sí gestiona planillas', function () {
    // El rol instructor tiene el permiso "gestionar planillas".
    $user = usuarioPlanilla('instructor', $this->bekho->id);

    $this->actingAs($user)->get(route('planillas.index'))->assertOk();
});

// --- Transversalidad ---------------------------------------------------------

test('las planillas son transversales: se ven desde cualquier academia', function () {
    $otra = Academia::create(['nombre' => 'OTRA', 'activo' => true]);
    nuevaPlanilla(extra: ['nombre' => 'Compartida A']);
    nuevaPlanilla(extra: ['nombre' => 'Compartida B']);

    // Con cualquier academia activa se ven todas (contenido compartido ATA).
    Tenant::set($this->bekho->id);
    expect(Planilla::count())->toBe(2);

    Tenant::set($otra->id);
    expect(Planilla::count())->toBe(2);
});

// --- Regla central: estructura de la planilla --------------------------------

test('una planilla nueva genera sus 9 bloques y 4 cuadrantes en orden', function () {
    Tenant::set($this->bekho->id);
    $planilla = nuevaPlanilla($this->bekho->id);
    $planilla->generarEstructura();

    expect($planilla->bloques()->count())->toBe(count(TipoBloque::cases()))->toBe(9);
    expect($planilla->cuadrantes()->count())->toBe(count(Cuadrante::cases()))->toBe(4);

    // Primer bloque = Calentamiento; último = Anuncios/Premios.
    $tipos = $planilla->bloques->pluck('tipo');
    expect($tipos->first())->toBe(TipoBloque::Calentamiento);
    expect($tipos->last())->toBe(TipoBloque::AnunciosPremios);
});

// --- Enlace clase → planilla -------------------------------------------------

test('una clase puede apuntar a su planilla', function () {
    Tenant::set($this->bekho->id);
    $sede = Sede::create(['academia_id' => $this->bekho->id, 'nombre' => 'Central', 'activo' => true]);
    $planilla = nuevaPlanilla($this->bekho->id, ['nombre' => 'Rutina Kids']);

    $clase = Clase::create([
        'academia_id' => $this->bekho->id, 'sede_id' => $sede->id, 'planilla_id' => $planilla->id,
        'nombre' => 'Kids AM', 'grupo_etario' => 'for_kids',
        'dia_semana' => 1, 'hora_inicio' => '10:00', 'activo' => true,
    ]);

    expect($clase->planilla->id)->toBe($planilla->id);
    expect($planilla->clases()->count())->toBe(1);
});

// --- Render ------------------------------------------------------------------

test('la pantalla de editar planilla renderiza para un instructor', function () {
    Tenant::set($this->bekho->id);
    $planilla = nuevaPlanilla($this->bekho->id);
    $planilla->generarEstructura();

    $user = usuarioPlanilla('instructor', $this->bekho->id);

    Tenant::olvidar();
    $this->actingAs($user)->get(route('planillas.editar', $planilla))->assertOk();
});

test('editar planilla renderiza con bloques sin tipo (actividad con nombre propio)', function () {
    Tenant::set($this->bekho->id);
    $planilla = nuevaPlanilla($this->bekho->id, ['grupo_etario' => 'tigers']);
    // Bloque con tipo nulo pero título propio (caso Tigers: "Juego de Golpes").
    $planilla->bloques()->create(['tipo' => null, 'titulo' => '⚔️ Juego de Golpes', 'orden' => 1]);

    $user = usuarioPlanilla('instructor', $this->bekho->id);

    Tenant::olvidar();
    $this->actingAs($user)->get(route('planillas.editar', $planilla))
        ->assertOk()
        ->assertSee('Juego de Golpes');
});
