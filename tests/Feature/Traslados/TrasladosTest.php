<?php

use App\Enums\EstadoMatricula;
use App\Enums\EstadoTraslado;
use App\Enums\TipoPago;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Persona;
use App\Models\Sede;
use App\Services\ServicioPagos;
use App\Services\ServicioTraslados;
use App\Support\Tenancy\Grupo as Tenant;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->origen = Grupo::where('nombre', 'BEKHO Power Academy')->first();
    $this->destino = Grupo::create(['nombre' => 'BEKHO Norte', 'activo' => true]);
    $this->sedeDestino = Sede::create(['grupo_id' => $this->destino->id, 'nombre' => 'Norte Central', 'activo' => true]);

    $this->persona = Persona::create(['nombres' => 'Alumno', 'apellido_paterno' => 'Trasladado', 'fecha_nacimiento' => now()->subYears(20)]);
    $this->matriculaOrigen = Matricula::withoutGlobalScopes()->create([
        'grupo_id' => $this->origen->id, 'persona_id' => $this->persona->id,
        'grupo_etario' => 'jovenes_adultos', 'estado' => 'activa', 'fecha_ingreso' => now(),
    ]);
});

afterEach(fn () => Tenant::olvidar());

/** Deja la matrícula de origen al día para que no bloquee por deuda. */
function alDia(Matricula $matricula): void
{
    app(ServicioPagos::class)->registrarPago($matricula, TipoPago::Mensualidad, 30000, now());
}

test('solicitar crea una solicitud pendiente de consentimiento', function () {
    $solicitud = app(ServicioTraslados::class)
        ->solicitar($this->persona, $this->matriculaOrigen, $this->destino, $this->sedeDestino);

    expect($solicitud->estado)->toBe(EstadoTraslado::PendienteConsentimiento)
        ->and($solicitud->plazo_desde)->not->toBeNull();
});

test('una persona no puede tener dos solicitudes pendientes a la vez', function () {
    $servicio = app(ServicioTraslados::class);
    $servicio->solicitar($this->persona, $this->matriculaOrigen, $this->destino);

    expect(fn () => $servicio->solicitar($this->persona, $this->matriculaOrigen, $this->destino))
        ->toThrow(QueryException::class);
});

test('consentir sin deuda pasa a pendiente de aprobación', function () {
    alDia($this->matriculaOrigen);
    $servicio = app(ServicioTraslados::class);
    $solicitud = $servicio->solicitar($this->persona, $this->matriculaOrigen, $this->destino);

    $servicio->consentir($solicitud, $this->persona);

    expect($solicitud->fresh()->estado)->toBe(EstadoTraslado::PendienteAprobacion);
});

test('consentir con deuda en el origen bloquea la solicitud', function () {
    $servicio = app(ServicioTraslados::class);
    $solicitud = $servicio->solicitar($this->persona, $this->matriculaOrigen, $this->destino);

    // La matrícula de origen está morosa (sin mensualidad) → bloquea.
    $servicio->consentir($solicitud, $this->persona);

    expect($solicitud->fresh()->estado)->toBe(EstadoTraslado::Bloqueada);

    // Al pagar la deuda y reevaluar, se desbloquea.
    alDia($this->matriculaOrigen);
    $servicio->reevaluarBloqueo($solicitud->fresh());

    expect($solicitud->fresh()->estado)->toBe(EstadoTraslado::PendienteAprobacion);
});

test('aprobar retira la matrícula de origen y crea la de destino', function () {
    alDia($this->matriculaOrigen);
    $servicio = app(ServicioTraslados::class);
    $solicitud = $servicio->solicitar($this->persona, $this->matriculaOrigen, $this->destino, $this->sedeDestino);
    $servicio->consentir($solicitud, $this->persona);

    $destino = $servicio->aprobar($solicitud);

    expect($destino)->not->toBeNull()
        ->and($destino->grupo_id)->toBe($this->destino->id)
        ->and($destino->estado)->toBe(EstadoMatricula::Activa)
        ->and($destino->matricula_origen_id)->toBe($this->matriculaOrigen->id)
        ->and($solicitud->fresh()->estado)->toBe(EstadoTraslado::Completada);

    // El origen queda retirado por traslado.
    $origen = $this->matriculaOrigen->fresh();
    expect($origen->estado)->toBe(EstadoMatricula::Retirada)
        ->and($origen->motivo_baja)->toBe('traslado');

    // Sigue habiendo una sola matrícula activa para la persona.
    expect(Matricula::withoutGlobalScopes()->where('persona_id', $this->persona->id)->where('estado', 'activa')->count())
        ->toBe(1);
});

test('no se puede aprobar una solicitud aún sin consentimiento', function () {
    alDia($this->matriculaOrigen);
    $servicio = app(ServicioTraslados::class);
    $solicitud = $servicio->solicitar($this->persona, $this->matriculaOrigen, $this->destino);

    expect($servicio->aprobar($solicitud))->toBeNull()
        ->and($solicitud->fresh()->estado)->toBe(EstadoTraslado::PendienteConsentimiento);
});

test('la solicitud de traslado es transversal (no lleva grupo_id)', function () {
    expect(Schema::hasColumn('solicitudes_traslado', 'grupo_id'))->toBeFalse();
});
