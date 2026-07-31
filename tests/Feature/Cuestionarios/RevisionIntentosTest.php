<?php

use App\Enums\EstadoIntento;
use App\Livewire\Cuestionarios\MisIntentos;
use App\Livewire\Cuestionarios\ResultadosCuestionarios;
use App\Livewire\Notificaciones;
use App\Models\Academia;
use App\Models\Cuestionario;
use App\Models\IntentoCuestionario;
use App\Models\User;
use App\Support\Tenancy\Academia as Tenant;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->bekho = Academia::where('nombre', 'BEKHO Power Academy')->first();
    Tenant::set($this->bekho->id);

    $this->cuestionario = Cuestionario::create(['titulo' => 'Prueba', 'activo' => true, 'umbral_aprobacion' => 80]);
});

afterEach(fn () => Tenant::olvidar());

function usuarioRev(string $rol, int $academiaId): User
{
    $u = User::factory()->create(['academia_id' => $academiaId]);
    $u->assignRole($rol);

    return $u;
}

function intentoDe(User $user, Cuestionario $c, int $porcentaje, int $academiaId): IntentoCuestionario
{
    return IntentoCuestionario::create([
        'academia_id' => $academiaId,
        'user_id' => $user->id,
        'cuestionario_id' => $c->id,
        'correctas' => $porcentaje,
        'total' => 100,
        'porcentaje' => $porcentaje,
        'aprobado' => $porcentaje >= $c->umbral_aprobacion,
        'finalizado_at' => now(),
    ]);
}

// --- Estado inicial ----------------------------------------------------------

test('un intento nace en revisión (pendiente del examinador)', function () {
    $alumno = usuarioRev('alumno', $this->bekho->id);
    $intento = intentoDe($alumno, $this->cuestionario, 90, $this->bekho->id);

    expect($intento->estado)->toBe(EstadoIntento::Pendiente);
});

// --- Panel del alumno --------------------------------------------------------

test('el alumno ve sus intentos y su estado', function () {
    $alumno = usuarioRev('alumno', $this->bekho->id);
    intentoDe($alumno, $this->cuestionario, 90, $this->bekho->id);

    Livewire::actingAs($alumno)->test(MisIntentos::class)
        ->assertSee('Prueba')
        ->assertSee('En revisión');
});

test('mis intentos solo muestra los propios', function () {
    $alumno = usuarioRev('alumno', $this->bekho->id);
    $otro = usuarioRev('alumno', $this->bekho->id);
    intentoDe($otro, $this->cuestionario, 90, $this->bekho->id);

    Livewire::actingAs($alumno)->test(MisIntentos::class)
        ->assertDontSee('Prueba');
});

// --- Decisión del examinador -------------------------------------------------

test('el examinador aprueba un intento que alcanzó el umbral', function () {
    $examinador = usuarioRev('instructor', $this->bekho->id);
    $alumno = usuarioRev('alumno', $this->bekho->id);
    $intento = intentoDe($alumno, $this->cuestionario, 90, $this->bekho->id);

    Livewire::actingAs($examinador)->test(ResultadosCuestionarios::class)
        ->call('abrirRevision', $intento->id)
        ->call('aprobar', $intento->id)
        ->assertHasNoErrors();

    $intento->refresh();
    expect($intento->estado)->toBe(EstadoIntento::Aprobado)
        ->and($intento->revisado_por)->toBe($examinador->id)
        ->and($intento->revisado_at)->not->toBeNull();
});

test('aprobar por debajo del umbral exige justificación', function () {
    $examinador = usuarioRev('instructor', $this->bekho->id);
    $alumno = usuarioRev('alumno', $this->bekho->id);
    $intento = intentoDe($alumno, $this->cuestionario, 40, $this->bekho->id); // reprobado

    // Sin justificación: falla y no cambia el estado.
    Livewire::actingAs($examinador)->test(ResultadosCuestionarios::class)
        ->call('abrirRevision', $intento->id)
        ->call('aprobar', $intento->id)
        ->assertHasErrors('justificacion');

    expect($intento->refresh()->estado)->toBe(EstadoIntento::Pendiente);

    // Con justificación: aprueba por excepción.
    Livewire::actingAs($examinador)->test(ResultadosCuestionarios::class)
        ->call('abrirRevision', $intento->id)
        ->set('justificacion', 'Aprobado por lesión durante la prueba; demostró la teoría en clase.')
        ->call('aprobar', $intento->id)
        ->assertHasNoErrors();

    $intento->refresh();
    expect($intento->estado)->toBe(EstadoIntento::Aprobado)
        ->and($intento->justificacion)->toContain('lesión');
});

test('el examinador puede pedir volver a intentar', function () {
    $examinador = usuarioRev('instructor', $this->bekho->id);
    $alumno = usuarioRev('alumno', $this->bekho->id);
    $intento = intentoDe($alumno, $this->cuestionario, 55, $this->bekho->id);

    Livewire::actingAs($examinador)->test(ResultadosCuestionarios::class)
        ->call('abrirRevision', $intento->id)
        ->set('justificacion', 'Repasa el reglamento de sparring y vuelve a rendir.')
        ->call('reintentar', $intento->id)
        ->assertHasNoErrors();

    expect($intento->refresh()->estado)->toBe(EstadoIntento::Reintentar);
});

// --- Notificación al alumno --------------------------------------------------

test('al decidir un intento, el alumno recibe una notificación', function () {
    $examinador = usuarioRev('instructor', $this->bekho->id);
    $alumno = usuarioRev('alumno', $this->bekho->id);
    $intento = intentoDe($alumno, $this->cuestionario, 90, $this->bekho->id);

    Livewire::actingAs($examinador)->test(ResultadosCuestionarios::class)
        ->call('abrirRevision', $intento->id)
        ->call('aprobar', $intento->id);

    $alumno->refresh();
    expect($alumno->notifications()->count())->toBe(1);

    $data = $alumno->notifications()->first()->data;
    expect($data['estado'])->toBe('aprobado')
        ->and($data['cuestionario'])->toBe('Prueba')
        ->and($data['titulo'])->toContain('aprobado');

    // El examinador que decide NO se auto-notifica.
    expect($examinador->notifications()->count())->toBe(0);
});

test('la notificación de reintento refleja la decisión', function () {
    $examinador = usuarioRev('instructor', $this->bekho->id);
    $alumno = usuarioRev('alumno', $this->bekho->id);
    $intento = intentoDe($alumno, $this->cuestionario, 55, $this->bekho->id);

    Livewire::actingAs($examinador)->test(ResultadosCuestionarios::class)
        ->call('abrirRevision', $intento->id)
        ->set('justificacion', 'Repasa el reglamento y vuelve a rendir.')
        ->call('reintentar', $intento->id);

    $data = $alumno->fresh()->notifications()->first()->data;
    expect($data['estado'])->toBe('reintentar')
        ->and($data['justificacion'])->toContain('Repasa');
});

test('la bandeja muestra la notificación y permite marcarla leída', function () {
    $alumno = usuarioRev('alumno', $this->bekho->id);
    $intento = intentoDe($alumno, $this->cuestionario, 90, $this->bekho->id);
    $examinador = usuarioRev('instructor', $this->bekho->id);

    Livewire::actingAs($examinador)->test(ResultadosCuestionarios::class)
        ->call('abrirRevision', $intento->id)
        ->call('aprobar', $intento->id);

    Livewire::actingAs($alumno)->test(Notificaciones::class)
        ->assertSee('Tu intento fue aprobado')
        ->call('marcarTodasLeidas')
        ->assertOk();

    expect($alumno->fresh()->unreadNotifications()->count())->toBe(0);
});

// --- Permisos ----------------------------------------------------------------

test('el panel de resultados es solo del examinador', function () {
    $alumno = usuarioRev('alumno', $this->bekho->id);
    $instructor = usuarioRev('instructor', $this->bekho->id);

    $this->actingAs($alumno)->get(route('cuestionarios.resultados'))->assertForbidden();
    $this->actingAs($alumno)->get(route('cuestionarios.mis-intentos'))->assertOk();
    $this->actingAs($instructor)->get(route('cuestionarios.resultados'))->assertOk();
});
