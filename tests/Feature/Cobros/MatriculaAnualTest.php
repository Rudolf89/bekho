<?php

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
    $this->sede = Sede::create(['grupo_id' => $this->bekho->id, 'nombre' => 'Central', 'activo' => true]);
    $this->tipoMatricula = TipoCargo::where('nombre', 'Matrícula')->first();
    TarifaSede::create(['sede_id' => $this->sede->id, 'tipo_cargo_id' => $this->tipoMatricula->id, 'cantidad_alumnos' => 1, 'monto_por_alumno' => 20000]);
    Tenant::set($this->bekho->id);
});

afterEach(fn () => Tenant::olvidar());

function matriculaAnual(int $grupoId, int $sedeId, string $ingreso): Matricula
{
    $persona = Persona::create(['nombres' => 'Alumno '.uniqid(), 'fecha_nacimiento' => now()->subYears(11)]);

    return Matricula::create([
        'grupo_id' => $grupoId, 'persona_id' => $persona->id, 'sede_id' => $sedeId,
        'grupo_etario' => 'for_kids', 'estado' => 'activa', 'fecha_ingreso' => $ingreso,
    ]);
}

test('genera la matrícula anual por matrícula activa (idempotente, monto de la sede)', function () {
    matriculaAnual($this->bekho->id, $this->sede->id, '2025-06-01'); // fuera de la ventana de exención
    $servicio = app(ServicioCargos::class);

    expect($servicio->generarMatriculasAnuales(2026))->toBe(1);
    expect($servicio->generarMatriculasAnuales(2026))->toBe(0); // idempotente

    $cargo = Cargo::withoutGlobalScopes()->where('tipo_cargo_id', $this->tipoMatricula->id)->first();
    expect($cargo->monto)->toBe(20000)
        ->and($cargo->periodo->format('Y-m-d'))->toBe('2026-01-01');
});

test('el alumno que ingresó en octubre–enero queda exento de la matrícula del año siguiente', function () {
    $exenta = matriculaAnual($this->bekho->id, $this->sede->id, '2025-11-15');
    $servicio = app(ServicioCargos::class);

    expect($servicio->exentaDeMatricula($exenta, 2026))->toBeTrue()
        ->and($servicio->generarMatriculasAnuales(2026))->toBe(0)
        ->and(Cargo::withoutGlobalScopes()->count())->toBe(0);
});

test('el alumno que ingresó fuera de esa ventana sí paga matrícula', function () {
    $normal = matriculaAnual($this->bekho->id, $this->sede->id, '2026-03-05');
    $servicio = app(ServicioCargos::class);

    expect($servicio->exentaDeMatricula($normal, 2026))->toBeFalse()
        ->and($servicio->generarMatriculasAnuales(2026))->toBe(1);
});
