<?php

use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Persona;
use App\Models\Sede;
use App\Models\Suspension;
use App\Services\ServicioPagos;
use App\Support\Tenancy\Grupo as Tenant;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->bekho = Grupo::where('nombre', 'BEKHO Power Academy')->first();
    $this->sede = Sede::create(['grupo_id' => $this->bekho->id, 'nombre' => 'Central', 'activo' => true]);
    Tenant::set($this->bekho->id);
});

afterEach(fn () => Tenant::olvidar());

function matriculaSuspension(int $grupoId, int $sedeId): Matricula
{
    $persona = Persona::create(['nombres' => 'Alumno', 'fecha_nacimiento' => now()->subYears(11)]);

    return Matricula::create([
        'grupo_id' => $grupoId, 'persona_id' => $persona->id, 'sede_id' => $sedeId,
        'grupo_etario' => 'for_kids', 'estado' => 'activa', 'fecha_ingreso' => now(),
    ]);
}

test('la suspensión es un dato operativo del grupo', function () {
    expect(Schema::hasColumn('suspensiones', 'grupo_id'))->toBeTrue();
});

test('una matrícula suspendida en el período no aparece como morosa', function () {
    $matricula = matriculaSuspension($this->bekho->id, $this->sede->id);
    $servicio = app(ServicioPagos::class);

    // Sin suspensión ni pago: morosa.
    expect($servicio->estaMoroso($matricula))->toBeTrue()
        ->and($servicio->morosos())->toHaveCount(1);

    // Suspendida cubriendo el mes actual: deja de ser morosa.
    Suspension::create([
        'grupo_id' => $this->bekho->id, 'matricula_id' => $matricula->id,
        'desde' => now()->startOfMonth(), 'hasta' => now()->endOfMonth(), 'motivo' => 'Viaje',
    ]);

    expect($servicio->estaMoroso($matricula->fresh()))->toBeFalse()
        ->and($servicio->morosos())->toHaveCount(0);
});

test('una suspensión de otro mes no exime la morosidad del mes actual', function () {
    $matricula = matriculaSuspension($this->bekho->id, $this->sede->id);

    // Suspensión el mes pasado (ya terminada).
    Suspension::create([
        'grupo_id' => $this->bekho->id, 'matricula_id' => $matricula->id,
        'desde' => now()->subMonth()->startOfMonth(), 'hasta' => now()->subMonth()->endOfMonth(),
    ]);

    expect(app(ServicioPagos::class)->estaMoroso($matricula->fresh()))->toBeTrue();
});

test('una suspensión abierta (sin hasta) cubre el período actual', function () {
    $matricula = matriculaSuspension($this->bekho->id, $this->sede->id);

    Suspension::create([
        'grupo_id' => $this->bekho->id, 'matricula_id' => $matricula->id,
        'desde' => now()->subMonths(2)->startOfMonth(), 'hasta' => null,
    ]);

    expect($matricula->fresh()->estaSuspendidaEn(now()))->toBeTrue()
        ->and(app(ServicioPagos::class)->estaMoroso($matricula->fresh()))->toBeFalse();
});
