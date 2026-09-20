<?php

use App\Enums\EstadoCargo;
use App\Models\Beca;
use App\Models\Cargo;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Persona;
use App\Models\Sede;
use App\Models\Suspension;
use App\Models\TarifaSede;
use App\Models\TipoCargo;
use App\Models\Tutela;
use App\Services\ServicioCargos;
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
    $this->sede = Sede::create(['grupo_id' => $this->bekho->id, 'nombre' => 'Central', 'activo' => true]);
    $this->mensualidad = TipoCargo::where('nombre', 'Mensualidad')->first();

    // Tarifa de LA SEDE: 1 alumno = 30.000; 2+ alumnos = 25.000 c/u.
    tarifa($this->sede, $this->mensualidad, 1, 30000);
    tarifa($this->sede, $this->mensualidad, 2, 25000);
});

afterEach(fn () => Tenant::olvidar());

function tarifa(Sede $sede, TipoCargo $tipo, int $cantidad, int $monto): TarifaSede
{
    return TarifaSede::create([
        'sede_id' => $sede->id, 'tipo_cargo_id' => $tipo->id,
        'cantidad_alumnos' => $cantidad, 'monto_por_alumno' => $monto,
    ]);
}

function matriculaCobro(int $grupoId, int $sedeId, ?Persona $persona = null): Matricula
{
    $persona ??= Persona::create(['nombres' => 'Alumno '.uniqid(), 'fecha_nacimiento' => now()->subYears(11)]);

    return Matricula::withoutGlobalScopes()->create([
        'grupo_id' => $grupoId, 'persona_id' => $persona->id, 'sede_id' => $sedeId,
        'grupo_etario' => 'for_kids', 'estado' => 'activa', 'fecha_ingreso' => now(), 'dia_vencimiento' => 5,
    ]);
}

test('genera un cargo de mensualidad por matrícula activa (idempotente)', function () {
    matriculaCobro($this->bekho->id, $this->sede->id);
    $servicio = app(ServicioCargos::class);

    expect($servicio->generarMensualidades())->toBe(1);
    expect($servicio->generarMensualidades())->toBe(0)
        ->and(Cargo::withoutGlobalScopes()->count())->toBe(1);

    $cargo = Cargo::withoutGlobalScopes()->first();
    expect($cargo->monto)->toBe(30000)
        ->and($cargo->sede_id)->toBe($this->sede->id)
        ->and($cargo->estado)->toBe(EstadoCargo::Pendiente)
        ->and($cargo->detalle_calculo['familia'])->toBe(1)
        ->and($cargo->detalle_calculo['sede_id'])->toBe($this->sede->id);
});

test('una matrícula suspendida en el período no genera cargo', function () {
    $matricula = matriculaCobro($this->bekho->id, $this->sede->id);
    Suspension::create(['grupo_id' => $this->bekho->id, 'matricula_id' => $matricula->id,
        'desde' => now()->startOfMonth(), 'hasta' => now()->endOfMonth()]);

    expect(app(ServicioCargos::class)->generarMensualidades())->toBe(0)
        ->and(Cargo::withoutGlobalScopes()->count())->toBe(0);
});

test('dos hermanos en la MISMA sede reciben el tramo de 2', function () {
    $apoderado = Persona::create(['nombres' => 'Madre', 'fecha_nacimiento' => now()->subYears(40)]);
    $h1 = Persona::create(['nombres' => 'Hermano 1', 'fecha_nacimiento' => now()->subYears(10)]);
    $h2 = Persona::create(['nombres' => 'Hermano 2', 'fecha_nacimiento' => now()->subYears(12)]);
    foreach ([$h1, $h2] as $hijo) {
        Tutela::create(['apoderado_persona_id' => $apoderado->id, 'alumno_persona_id' => $hijo->id, 'parentesco' => 'madre', 'responsable_pago' => true]);
    }
    $m1 = matriculaCobro($this->bekho->id, $this->sede->id, $h1);
    matriculaCobro($this->bekho->id, $this->sede->id, $h2);

    $servicio = app(ServicioCargos::class);
    expect($servicio->tamanoFamilia($m1))->toBe(2);

    $servicio->generarMensualidades();
    expect(Cargo::withoutGlobalScopes()->where('matricula_id', $m1->id)->first()->monto)->toBe(25000);
});

test('dos hermanos en SEDES DISTINTAS pagan cada uno como alumno único', function () {
    $otraSede = Sede::create(['grupo_id' => $this->bekho->id, 'nombre' => 'Norte', 'activo' => true]);
    tarifa($otraSede, $this->mensualidad, 1, 30000);
    tarifa($otraSede, $this->mensualidad, 2, 25000);

    $apoderado = Persona::create(['nombres' => 'Padre', 'fecha_nacimiento' => now()->subYears(40)]);
    $h1 = Persona::create(['nombres' => 'Uno', 'fecha_nacimiento' => now()->subYears(10)]);
    $h2 = Persona::create(['nombres' => 'Dos', 'fecha_nacimiento' => now()->subYears(12)]);
    foreach ([$h1, $h2] as $hijo) {
        Tutela::create(['apoderado_persona_id' => $apoderado->id, 'alumno_persona_id' => $hijo->id, 'parentesco' => 'padre', 'responsable_pago' => true]);
    }
    $m1 = matriculaCobro($this->bekho->id, $this->sede->id, $h1);
    $m2 = matriculaCobro($this->bekho->id, $otraSede->id, $h2);

    $servicio = app(ServicioCargos::class);
    expect($servicio->tamanoFamilia($m1))->toBe(1)
        ->and($servicio->tamanoFamilia($m2))->toBe(1);

    $servicio->generarMensualidades();
    expect(Cargo::withoutGlobalScopes()->where('matricula_id', $m1->id)->first()->monto)->toBe(30000)
        ->and(Cargo::withoutGlobalScopes()->where('matricula_id', $m2->id)->first()->monto)->toBe(30000);
});

test('sedes distintas con tarifas distintas generan montos distintos', function () {
    $cara = Sede::create(['grupo_id' => $this->bekho->id, 'nombre' => 'Premium', 'activo' => true]);
    tarifa($cara, $this->mensualidad, 1, 50000);

    $mBarata = matriculaCobro($this->bekho->id, $this->sede->id);
    $mCara = matriculaCobro($this->bekho->id, $cara->id);

    app(ServicioCargos::class)->generarMensualidades();

    expect(Cargo::withoutGlobalScopes()->where('matricula_id', $mBarata->id)->first()->monto)->toBe(30000)
        ->and(Cargo::withoutGlobalScopes()->where('matricula_id', $mCara->id)->first()->monto)->toBe(50000);
});

test('una sede sin tarifa falla de forma explícita, sin valor por defecto', function () {
    $sinTarifa = Sede::create(['grupo_id' => $this->bekho->id, 'nombre' => 'Nueva', 'activo' => true]);
    matriculaCobro($this->bekho->id, $sinTarifa->id);

    expect(fn () => app(ServicioCargos::class)->generarMensualidades())
        ->toThrow(RuntimeException::class);

    expect(Cargo::withoutGlobalScopes()->count())->toBe(0);
});

test('la beca vigente rebaja el monto sobre el tramo de la sede', function () {
    $matricula = matriculaCobro($this->bekho->id, $this->sede->id);
    Beca::create(['grupo_id' => $this->bekho->id, 'matricula_id' => $matricula->id,
        'tipo' => 'porcentaje', 'valor' => 50, 'motivo' => 'Convenio']);

    app(ServicioCargos::class)->generarMensualidades();

    // 30.000 (tramo 1 de la sede) con 50% de beca => 15.000.
    expect(Cargo::withoutGlobalScopes()->where('matricula_id', $matricula->id)->first()->monto)->toBe(15000);
});

test('un cargo ya emitido no cambia al modificar la tarifa de su sede', function () {
    $matricula = matriculaCobro($this->bekho->id, $this->sede->id);
    $servicio = app(ServicioCargos::class);
    $servicio->generarMensualidades();

    $cargo = Cargo::withoutGlobalScopes()->where('matricula_id', $matricula->id)->first();
    expect($cargo->monto)->toBe(30000);

    // Se sube la tarifa de la sede; el cargo pasado no se recalcula.
    TarifaSede::where('sede_id', $this->sede->id)->where('cantidad_alumnos', 1)->update(['monto_por_alumno' => 99000]);
    $servicio->generarMensualidades(); // idempotente para el período

    expect($cargo->fresh()->monto)->toBe(30000)
        ->and($cargo->fresh()->detalle_calculo['monto_base'])->toBe(30000);
});

test('tieneDeuda detecta un cargo pendiente del período', function () {
    $matricula = matriculaCobro($this->bekho->id, $this->sede->id);
    $servicio = app(ServicioCargos::class);

    expect($servicio->tieneDeuda($matricula))->toBeFalse();

    $servicio->generarMensualidades();

    expect($servicio->tieneDeuda($matricula->fresh()))->toBeTrue();
});
