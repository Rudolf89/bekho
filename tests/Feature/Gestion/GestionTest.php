<?php

use App\Enums\TipoPago;
use App\Models\Clase;
use App\Models\ConfiguracionPago;
use App\Models\Estudiante;
use App\Models\Grupo;
use App\Models\Sede;
use App\Models\User;
use App\Services\ServicioPagos;
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

/**
 * Usuario con rol, grupo y 2FA confirmada si el rol la exige.
 */
function actor(string $rol, ?int $grupoId): User
{
    $exige2fa = in_array($rol, config('bekho.2fa_obligatorio_para', []), true);
    $user = User::factory()->create([
        'grupo_id' => $grupoId,
        'two_factor_confirmed_at' => $exige2fa ? now() : null,
    ]);
    $user->assignRole($rol);

    return $user;
}

function nuevoEstudiante(int $grupoId, array $extra = []): Estudiante
{
    return Estudiante::create(array_merge([
        'grupo_id' => $grupoId,
        'nombre' => 'Alumno',
        'grupo_etario' => 'for_kids',
        'nivel' => 'principiantes',
        'activo' => true,
    ], $extra));
}

// --- Permisos ----------------------------------------------------------------

test('un instructor accede al listado de estudiantes (ve solo los de sus clases)', function () {
    $user = actor('instructor', $this->bekho->id);

    $this->actingAs($user)->get(route('estudiantes.index'))->assertOk();
});

test('un instructor no puede inscribir alumnos (eso es de dirección/administrativo)', function () {
    $user = actor('instructor', $this->bekho->id);

    $this->actingAs($user)->get(route('inscripcion.crear'))->assertForbidden();
});

test('un maestro accede a la gestión de estudiantes', function () {
    $user = actor('direccion', $this->bekho->id);

    $this->actingAs($user)->get(route('estudiantes.index'))->assertOk();
});

test('un instructor puede tomar asistencia pero no gestionar pagos', function () {
    $user = actor('instructor', $this->bekho->id);

    $this->actingAs($user)->get(route('asistencia.tomar'))->assertOk();
    $this->actingAs($user)->get(route('pagos.index'))->assertForbidden();
});

test('las pantallas de clases y pagos renderizan para un maestro', function () {
    $user = actor('direccion', $this->bekho->id);

    $this->actingAs($user)->get(route('clases.index'))->assertOk();
    $this->actingAs($user)->get(route('pagos.index'))->assertOk();
});

test('la gestión de estudiantes renderiza aunque existan apoderados', function () {
    // Regresión: el multiselect de apoderados debe usar componentes de Flux libre.
    actor('apoderado', $this->bekho->id);
    $user = actor('direccion', $this->bekho->id);

    $this->actingAs($user)->get(route('estudiantes.index'))->assertOk();
});

// --- Scope por grupo ------------------------------------------------------

test('los estudiantes se aíslan por grupo', function () {
    $otra = Grupo::create(['nombre' => 'OTRA', 'activo' => true]);
    nuevoEstudiante($this->bekho->id, ['nombre' => 'De BEKHO']);
    nuevoEstudiante($otra->id, ['nombre' => 'De OTRA']);

    Tenant::set($this->bekho->id);
    expect(Estudiante::count())->toBe(1);
    expect(Estudiante::first()->nombre)->toBe('De BEKHO');

    Tenant::set($otra->id);
    expect(Estudiante::count())->toBe(1);
    expect(Estudiante::first()->nombre)->toBe('De OTRA');
});

// --- Regla central: morosidad ------------------------------------------------

test('un estudiante activo sin mensualidad del período está moroso', function () {
    Tenant::set($this->bekho->id);
    $estudiante = nuevoEstudiante($this->bekho->id);
    $servicio = app(ServicioPagos::class);

    expect($servicio->estaMoroso($estudiante))->toBeTrue();

    $servicio->registrarPago($estudiante, TipoPago::Mensualidad, 30000, now());

    expect($servicio->estaMoroso($estudiante))->toBeFalse();
    expect($servicio->morosos())->toHaveCount(0);
});

test('registrar la mensualidad es idempotente en el mismo período', function () {
    Tenant::set($this->bekho->id);
    $estudiante = nuevoEstudiante($this->bekho->id);
    $servicio = app(ServicioPagos::class);

    $servicio->registrarPago($estudiante, TipoPago::Mensualidad, 30000, now());
    $servicio->registrarPago($estudiante, TipoPago::Mensualidad, 35000, now());

    expect($estudiante->pagos()->where('tipo', 'mensualidad')->count())->toBe(1);
    expect($estudiante->pagos()->where('tipo', 'mensualidad')->first()->monto)->toBe(35000);
});

// --- Descuento por hermanos --------------------------------------------------

test('el descuento por hermanos se aplica cuando comparten apoderado', function () {
    Tenant::set($this->bekho->id);

    $ana = nuevoEstudiante($this->bekho->id, ['nombre' => 'Ana']);
    $beto = nuevoEstudiante($this->bekho->id, ['nombre' => 'Beto']);
    $apoderado = actor('apoderado', $this->bekho->id);
    $ana->apoderados()->attach($apoderado->id);
    $beto->apoderados()->attach($apoderado->id);

    $servicio = app(ServicioPagos::class);
    expect($servicio->tieneHermanos($ana))->toBeTrue();

    // Con mensualidad de 10000 y 20% de descuento => 8000.
    $cfg = ConfiguracionPago::where('grupo_id', $this->bekho->id)->first();
    $cfg->update(['valor_mensualidad' => 10000, 'descuento_hermanos_pct' => 20]);

    expect($servicio->montoMensualidadEsperado($ana, $cfg->fresh()))->toBe(8000);
});

// --- Vista de apoderado (Policy) ---------------------------------------------

test('un apoderado solo ve a sus propios hijos', function () {
    Tenant::set($this->bekho->id);
    $ana = nuevoEstudiante($this->bekho->id, ['nombre' => 'Ana']);
    $ajeno = nuevoEstudiante($this->bekho->id, ['nombre' => 'Ajeno']);

    $papa = actor('apoderado', $this->bekho->id);
    $ana->apoderados()->attach($papa->id);

    expect($papa->can('view', $ana))->toBeTrue();
    expect($papa->can('view', $ajeno))->toBeFalse();

    $this->actingAs($papa)->get(route('mis-estudiantes.index'))
        ->assertOk()
        ->assertSee('Ana')
        ->assertDontSee('Ajeno');
});

// --- Asistencia --------------------------------------------------------------

test('el roster de una clase son los estudiantes activos de su sede y grupo etario', function () {
    // Las clases se dividen SOLO por grupo etario: el roster incluye a todos los
    // For Kids de la sede, sin importar su nivel; excluye otros grupos etarios.
    Tenant::set($this->bekho->id);
    $sede = Sede::create(['grupo_id' => $this->bekho->id, 'nombre' => 'Central', 'activo' => true]);

    nuevoEstudiante($this->bekho->id, ['nombre' => 'KidPrincipiante', 'sede_id' => $sede->id, 'grupo_etario' => 'for_kids', 'nivel' => 'principiantes']);
    nuevoEstudiante($this->bekho->id, ['nombre' => 'KidAvanzado', 'sede_id' => $sede->id, 'grupo_etario' => 'for_kids', 'nivel' => 'avanzado']);
    nuevoEstudiante($this->bekho->id, ['nombre' => 'Adulto', 'sede_id' => $sede->id, 'grupo_etario' => 'jovenes_adultos', 'nivel' => 'principiantes']);

    $clase = Clase::create([
        'grupo_id' => $this->bekho->id, 'sede_id' => $sede->id, 'nombre' => 'Kids',
        'grupo_etario' => 'for_kids', 'dia_semana' => 1,
        'hora_inicio' => '10:00', 'activo' => true,
    ]);

    $roster = $clase->estudiantesEsperados()->get();

    // Ambos For Kids (cualquier nivel) entran; el adulto no.
    expect($roster->pluck('nombre')->all())->toContain('KidPrincipiante', 'KidAvanzado');
    expect($roster->pluck('nombre')->all())->not->toContain('Adulto');
    expect($roster)->toHaveCount(2);
});
