<?php

use App\Enums\PlanPago;
use App\Models\Cargo;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Persona;
use App\Models\Sede;
use App\Models\TarifaSede;
use App\Models\TipoCargo;
use App\Services\ServicioCargos;
use App\Support\Tenancy\Grupo as Tenant;
use Database\Seeders\CatalogosFederacionSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    $this->seed(CatalogosFederacionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->bekho = Grupo::where('nombre', 'BEKHO Power Academy')->first();
    // Sede con descuentos: 10% semestral, 20% anual.
    $this->sede = Sede::create([
        'grupo_id' => $this->bekho->id, 'nombre' => 'Central', 'activo' => true,
        'descuento_semestral_pct' => 10, 'descuento_anual_pct' => 20,
    ]);
    $this->mensualidad = TipoCargo::where('nombre', 'Mensualidad')->first();
    TarifaSede::create([
        'sede_id' => $this->sede->id, 'tipo_cargo_id' => $this->mensualidad->id,
        'cantidad_alumnos' => 1, 'monto_por_alumno' => 30000,
    ]);
    Tenant::set($this->bekho->id);
});

afterEach(fn () => Tenant::olvidar());

function matriculaPlan(int $grupoId, int $sedeId, string $plan): Matricula
{
    $persona = Persona::create(['nombres' => 'Alumno '.uniqid(), 'fecha_nacimiento' => now()->subYears(11)]);

    return Matricula::withoutGlobalScopes()->create([
        'grupo_id' => $grupoId, 'persona_id' => $persona->id, 'sede_id' => $sedeId,
        'grupo_etario' => 'for_kids', 'estado' => 'activa', 'fecha_ingreso' => now(),
        'dia_vencimiento' => 5, 'plan_pago' => $plan,
    ]);
}

test('el plan semestral aplica el descuento de la sede sobre 6 meses', function () {
    $matricula = matriculaPlan($this->bekho->id, $this->sede->id, PlanPago::Semestral->value);
    $servicio = app(ServicioCargos::class);

    // 30.000 × 6 = 180.000, −10% = 162.000.
    [$monto, $detalle] = $servicio->montoPlan($matricula, $this->mensualidad, $servicio->periodo());

    expect($monto)->toBe(162000)
        ->and($detalle['meses'])->toBe(6)
        ->and($detalle['descuento_plan_pct'])->toBe(10)
        ->and($detalle['mensualidad'])->toBe(30000);
});

test('el plan anual aplica el descuento de la sede sobre 12 meses', function () {
    $matricula = matriculaPlan($this->bekho->id, $this->sede->id, PlanPago::Anual->value);
    $servicio = app(ServicioCargos::class);

    // 30.000 × 12 = 360.000, −20% = 288.000.
    [$monto] = $servicio->montoPlan($matricula, $this->mensualidad, $servicio->periodo());

    expect($monto)->toBe(288000);
});

test('generarCargoPlan emite el cargo por adelantado (idempotente) y omite el plan mensual', function () {
    $semestral = matriculaPlan($this->bekho->id, $this->sede->id, PlanPago::Semestral->value);
    $mensual = matriculaPlan($this->bekho->id, $this->sede->id, PlanPago::Mensual->value);
    $servicio = app(ServicioCargos::class);

    expect($servicio->generarCargoPlan($semestral))->not->toBeNull()
        ->and($servicio->generarCargoPlan($semestral))->toBeNull() // idempotente
        ->and($servicio->generarCargoPlan($mensual))->toBeNull(); // el mensual no usa cargo por adelantado

    $cargo = Cargo::withoutGlobalScopes()->where('matricula_id', $semestral->id)->first();
    expect($cargo->monto)->toBe(162000);
    expect(Cargo::withoutGlobalScopes()->where('matricula_id', $mensual->id)->count())->toBe(0);
});

test('generarMensualidades omite las matrículas con plan semestral o anual', function () {
    matriculaPlan($this->bekho->id, $this->sede->id, PlanPago::Semestral->value);
    matriculaPlan($this->bekho->id, $this->sede->id, PlanPago::Anual->value);
    $mensual = matriculaPlan($this->bekho->id, $this->sede->id, PlanPago::Mensual->value);
    $servicio = app(ServicioCargos::class);

    // Solo la matrícula mensual genera cargo mensual.
    expect($servicio->generarMensualidades())->toBe(1);
    expect(Cargo::withoutGlobalScopes()->where('matricula_id', $mensual->id)->count())->toBe(1);
});

test('sin descuento configurado el plan cobra la suma simple de los meses', function () {
    $sinDescuento = Sede::create(['grupo_id' => $this->bekho->id, 'nombre' => 'Norte', 'activo' => true]);
    TarifaSede::create([
        'sede_id' => $sinDescuento->id, 'tipo_cargo_id' => $this->mensualidad->id,
        'cantidad_alumnos' => 1, 'monto_por_alumno' => 30000,
    ]);
    $matricula = matriculaPlan($this->bekho->id, $sinDescuento->id, PlanPago::Semestral->value);
    $servicio = app(ServicioCargos::class);

    // 30.000 × 6 = 180.000 sin descuento.
    [$monto] = $servicio->montoPlan($matricula, $this->mensualidad, $servicio->periodo());
    expect($monto)->toBe(180000);
});
