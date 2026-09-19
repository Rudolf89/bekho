<?php

use App\Enums\EstadoCargo;
use App\Models\Beca;
use App\Models\Cargo;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Persona;
use App\Models\Sede;
use App\Models\Suspension;
use App\Models\TarifaGrupo;
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

    // Tarifa: 1 alumno = 30.000; 2+ alumnos = 25.000 c/u.
    TarifaGrupo::create(['grupo_id' => $this->bekho->id, 'tipo_cargo_id' => $this->mensualidad->id, 'cantidad_alumnos' => 1, 'monto_por_alumno' => 30000]);
    TarifaGrupo::create(['grupo_id' => $this->bekho->id, 'tipo_cargo_id' => $this->mensualidad->id, 'cantidad_alumnos' => 2, 'monto_por_alumno' => 25000]);
});

afterEach(fn () => Tenant::olvidar());

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
    // Volver a correr no duplica.
    expect($servicio->generarMensualidades())->toBe(0)
        ->and(Cargo::withoutGlobalScopes()->count())->toBe(1);

    $cargo = Cargo::withoutGlobalScopes()->first();
    expect($cargo->monto)->toBe(30000)
        ->and($cargo->estado)->toBe(EstadoCargo::Pendiente)
        ->and($cargo->detalle_calculo['familia'])->toBe(1);
});

test('una matrícula suspendida en el período no genera cargo', function () {
    $matricula = matriculaCobro($this->bekho->id, $this->sede->id);
    Suspension::create(['grupo_id' => $this->bekho->id, 'matricula_id' => $matricula->id,
        'desde' => now()->startOfMonth(), 'hasta' => now()->endOfMonth()]);

    expect(app(ServicioCargos::class)->generarMensualidades())->toBe(0)
        ->and(Cargo::withoutGlobalScopes()->count())->toBe(0);
});

test('el tramo por familia aplica el monto rebajado a los hermanos', function () {
    // Dos hermanos con el mismo apoderado responsable de pago.
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
    // Con familia de 2, el tramo de 25.000 aplica a cada hermano.
    expect(Cargo::withoutGlobalScopes()->where('matricula_id', $m1->id)->first()->monto)->toBe(25000);
});

test('la beca vigente rebaja el monto sobre el tramo', function () {
    $matricula = matriculaCobro($this->bekho->id, $this->sede->id);
    Beca::create(['grupo_id' => $this->bekho->id, 'matricula_id' => $matricula->id,
        'tipo' => 'porcentaje', 'valor' => 50, 'motivo' => 'Convenio']);

    app(ServicioCargos::class)->generarMensualidades();

    // 30.000 (tramo 1) con 50% de beca => 15.000.
    expect(Cargo::withoutGlobalScopes()->where('matricula_id', $matricula->id)->first()->monto)->toBe(15000);
});

test('tieneDeuda detecta un cargo pendiente del período', function () {
    $matricula = matriculaCobro($this->bekho->id, $this->sede->id);
    $servicio = app(ServicioCargos::class);

    expect($servicio->tieneDeuda($matricula))->toBeFalse();

    $servicio->generarMensualidades();

    expect($servicio->tieneDeuda($matricula->fresh()))->toBeTrue();
});
