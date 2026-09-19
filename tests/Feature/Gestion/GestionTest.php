<?php

use App\Models\Cargo;
use App\Models\Clase;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Persona;
use App\Models\Sede;
use App\Models\TipoCargo;
use App\Models\Tutela;
use App\Models\User;
use App\Services\ServicioCargos;
use App\Services\ServicioPagos;
use App\Support\Tenancy\Grupo as Tenant;
use Database\Seeders\CatalogosFederacionSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    $this->seed(CatalogosFederacionSeeder::class); // tipos_cargo (Mensualidad, …)
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->bekho = Grupo::where('nombre', 'BEKHO Power Academy')->first();
    $this->mensualidad = TipoCargo::where('recurrente', true)->orderBy('orden')->first();
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

/**
 * Persona con matrícula activa (alumno del nuevo modelo). Devuelve la matrícula.
 */
function nuevaMatricula(int $grupoId, string $nombre = 'Alumno', ?int $sedeId = null, string $grupoEtario = 'for_kids', ?int $gradoId = null): Matricula
{
    $persona = Persona::create(['nombres' => $nombre, 'fecha_nacimiento' => now()->subYears(10), 'grado_id' => $gradoId]);

    return Matricula::create([
        'grupo_id' => $grupoId,
        'persona_id' => $persona->id,
        'sede_id' => $sedeId,
        'grupo_etario' => $grupoEtario,
        'estado' => 'activa',
        'fecha_ingreso' => now(),
    ]);
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

test('las matrículas se aíslan por grupo', function () {
    $otra = Grupo::create(['nombre' => 'OTRA', 'activo' => true]);
    nuevaMatricula($this->bekho->id, 'De BEKHO');
    nuevaMatricula($otra->id, 'De OTRA');

    Tenant::set($this->bekho->id);
    expect(Matricula::count())->toBe(1);
    expect(Matricula::first()->persona->nombres)->toBe('De BEKHO');

    Tenant::set($otra->id);
    expect(Matricula::count())->toBe(1);
    expect(Matricula::first()->persona->nombres)->toBe('De OTRA');
});

// --- Regla central: morosidad ------------------------------------------------

/** Cargo de mensualidad pendiente del mes actual para la matrícula. */
function cargoDe(Matricula $matricula, int $monto = 30000): Cargo
{
    $tipo = TipoCargo::where('recurrente', true)->orderBy('orden')->first();

    return Cargo::withoutGlobalScopes()->create([
        'grupo_id' => $matricula->grupo_id, 'matricula_id' => $matricula->id,
        'tipo_cargo_id' => $tipo->id, 'periodo' => now()->startOfMonth(),
        'monto' => $monto, 'estado' => 'pendiente',
    ]);
}

test('una matrícula activa con un cargo pendiente está morosa', function () {
    Tenant::set($this->bekho->id);
    $matricula = nuevaMatricula($this->bekho->id);
    $servicio = app(ServicioPagos::class);

    // Sin cargos aún: nada que deber.
    expect($servicio->estaMoroso($matricula))->toBeFalse();

    cargoDe($matricula, 30000);
    expect($servicio->estaMoroso($matricula))->toBeTrue();
});

test('pagar el cargo del período lo marca pagado y sale de morosidad', function () {
    Tenant::set($this->bekho->id);
    $matricula = nuevaMatricula($this->bekho->id);
    $cargo = cargoDe($matricula, 30000);
    $servicio = app(ServicioPagos::class);

    $servicio->registrarPago($matricula, 30000, now());

    expect($cargo->fresh()->estado->value)->toBe('pagado')
        ->and($servicio->estaMoroso($matricula))->toBeFalse()
        ->and($servicio->morosos())->toHaveCount(0);
});

// --- Descuento por hermanos --------------------------------------------------

test('el tamaño de familia cuenta a los hermanos con el mismo responsable de pago', function () {
    Tenant::set($this->bekho->id);

    $ana = nuevaMatricula($this->bekho->id, 'Ana');
    $beto = nuevaMatricula($this->bekho->id, 'Beto');
    $apoderado = Persona::create(['nombres' => 'Papá', 'fecha_nacimiento' => now()->subYears(40)]);

    foreach ([$ana, $beto] as $hijo) {
        Tutela::create(['apoderado_persona_id' => $apoderado->id, 'alumno_persona_id' => $hijo->persona_id, 'parentesco' => 'padre', 'responsable_pago' => true]);
    }

    // La familia (para el tramo de tarifas) incluye a ambos hermanos.
    expect(app(ServicioCargos::class)->tamanoFamilia($ana))->toBe(2);
});

// --- Vista de apoderado (Policy) ---------------------------------------------

test('un apoderado solo ve a sus propios hijos', function () {
    Tenant::set($this->bekho->id);
    $ana = nuevaMatricula($this->bekho->id, 'Ana');
    $ajeno = nuevaMatricula($this->bekho->id, 'Ajeno');

    $papaPersona = Persona::create(['nombres' => 'Papá', 'fecha_nacimiento' => now()->subYears(40)]);
    $papa = actor('apoderado', $this->bekho->id);
    $papa->update(['persona_id' => $papaPersona->id]);

    // Solo tutela a Ana; la matrícula ajena no le es visible.
    Tutela::create(['apoderado_persona_id' => $papaPersona->id, 'alumno_persona_id' => $ana->persona_id, 'parentesco' => 'padre']);

    expect($papa->can('view', $ana))->toBeTrue();
    expect($papa->can('view', $ajeno))->toBeFalse();

    $this->actingAs($papa)->get(route('mis-estudiantes.index'))
        ->assertOk()
        ->assertSee('Ana')
        ->assertDontSee('Ajeno');
});

// --- Asistencia --------------------------------------------------------------

test('el roster de una clase son las matrículas activas de su sede y grupo etario', function () {
    // Las clases se dividen SOLO por grupo etario: el roster incluye a todos los
    // For Kids de la sede, sin importar su nivel; excluye otros grupos etarios.
    Tenant::set($this->bekho->id);
    $sede = Sede::create(['grupo_id' => $this->bekho->id, 'nombre' => 'Central', 'activo' => true]);

    nuevaMatricula($this->bekho->id, 'KidPrincipiante', $sede->id, 'for_kids');
    nuevaMatricula($this->bekho->id, 'KidAvanzado', $sede->id, 'for_kids');
    nuevaMatricula($this->bekho->id, 'Adulto', $sede->id, 'jovenes_adultos');

    $clase = Clase::create([
        'grupo_id' => $this->bekho->id, 'sede_id' => $sede->id, 'nombre' => 'Kids',
        'grupo_etario' => 'for_kids', 'activo' => true,
    ]);

    $roster = $clase->matriculasEsperadas()->get();

    // Ambos For Kids (cualquier nivel) entran; el adulto no.
    expect($roster->pluck('persona.nombres')->all())->toContain('KidPrincipiante', 'KidAvanzado');
    expect($roster->pluck('persona.nombres')->all())->not->toContain('Adulto');
    expect($roster)->toHaveCount(2);
});
