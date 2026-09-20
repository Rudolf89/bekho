<?php

use App\Livewire\Asistencia\TomarAsistencia;
use App\Models\Asistencia;
use App\Models\Cargo;
use App\Models\Clase;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Persona;
use App\Models\Sede;
use App\Models\TipoCargo;
use App\Models\User;
use App\Services\ServicioPagos;
use App\Support\Tenancy\Grupo as Tenant;
use Database\Seeders\CatalogosFederacionSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    $this->seed(CatalogosFederacionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->bekho = Grupo::where('nombre', 'BEKHO Power Academy')->first();
    $this->sede = Sede::create(['grupo_id' => $this->bekho->id, 'nombre' => 'Central', 'activo' => true]);
    $this->mensualidad = TipoCargo::where('recurrente', true)->orderBy('orden')->first();
    Tenant::set($this->bekho->id);

    $persona = Persona::create(['nombres' => 'Deudor', 'fecha_nacimiento' => now()->subYears(12)]);
    $this->matricula = Matricula::create([
        'grupo_id' => $this->bekho->id, 'persona_id' => $persona->id, 'sede_id' => $this->sede->id,
        'grupo_etario' => 'for_kids', 'estado' => 'activa', 'fecha_ingreso' => now()->subMonths(3),
    ]);

    // Cargo impago vencido hace 40 días.
    Cargo::create([
        'grupo_id' => $this->bekho->id, 'matricula_id' => $this->matricula->id, 'sede_id' => $this->sede->id,
        'tipo_cargo_id' => $this->mensualidad->id, 'periodo' => now()->subMonth()->startOfMonth(),
        'monto' => 30000, 'vence_el' => now()->subDays(40), 'estado' => 'pendiente',
    ]);

    $this->clase = Clase::create([
        'grupo_id' => $this->bekho->id, 'sede_id' => $this->sede->id, 'nombre' => 'Kids',
        'grupo_etario' => 'for_kids', 'activo' => true,
    ]);
});

afterEach(fn () => Tenant::olvidar());

function presente(int $grupoId, int $claseId, int $matriculaId, string $fecha): void
{
    Asistencia::create([
        'grupo_id' => $grupoId, 'clase_id' => $claseId, 'matricula_id' => $matriculaId,
        'fecha' => $fecha, 'estado' => 'presente',
    ]);
}

test('cuenta las clases asistidas después del vencimiento', function () {
    presente($this->bekho->id, $this->clase->id, $this->matricula->id, now()->subDays(30)->toDateString());
    presente($this->bekho->id, $this->clase->id, $this->matricula->id, now()->subDays(20)->toDateString());

    expect(app(ServicioPagos::class)->clasesDesdeVencimiento($this->matricula))->toBe(2);
});

test('no está bloqueado con menos de 3 clases de gracia', function () {
    presente($this->bekho->id, $this->clase->id, $this->matricula->id, now()->subDays(30)->toDateString());
    presente($this->bekho->id, $this->clase->id, $this->matricula->id, now()->subDays(20)->toDateString());

    expect(app(ServicioPagos::class)->estaBloqueadoPorDeuda($this->matricula))->toBeFalse();
});

test('queda bloqueado al superar las 3 clases de gracia', function () {
    foreach ([30, 20, 10] as $dias) {
        presente($this->bekho->id, $this->clase->id, $this->matricula->id, now()->subDays($dias)->toDateString());
    }

    expect(app(ServicioPagos::class)->estaBloqueadoPorDeuda($this->matricula))->toBeTrue();
});

test('un alumno al día no se bloquea', function () {
    // Sin cargos vencidos: se paga el cargo → no hay vencimiento impago.
    $this->matricula->cargos()->update(['estado' => 'pagado']);
    foreach ([30, 20, 10] as $dias) {
        presente($this->bekho->id, $this->clase->id, $this->matricula->id, now()->subDays($dias)->toDateString());
    }

    expect(app(ServicioPagos::class)->estaBloqueadoPorDeuda($this->matricula))->toBeFalse();
});

test('la toma de asistencia no deja marcar presente a un alumno bloqueado', function () {
    foreach ([30, 20, 10] as $dias) {
        presente($this->bekho->id, $this->clase->id, $this->matricula->id, now()->subDays($dias)->toDateString());
    }
    $direccion = User::factory()->create(['grupo_id' => $this->bekho->id, 'two_factor_confirmed_at' => now()]);
    $direccion->assignRole('direccion');

    Livewire::actingAs($direccion)->test(TomarAsistencia::class)
        ->set('claseId', (string) $this->clase->id)
        ->set('fecha', now()->toDateString())
        ->call('marcar', $this->matricula->id, 'presente');

    // No se registró presente para hoy (quedó bloqueado); sí se permite ausente.
    expect(Asistencia::where('matricula_id', $this->matricula->id)->whereDate('fecha', now()->toDateString())->exists())->toBeFalse();

    Livewire::actingAs($direccion)->test(TomarAsistencia::class)
        ->set('claseId', (string) $this->clase->id)
        ->set('fecha', now()->toDateString())
        ->call('marcar', $this->matricula->id, 'ausente');

    expect(Asistencia::where('matricula_id', $this->matricula->id)->whereDate('fecha', now()->toDateString())->where('estado', 'ausente')->exists())->toBeTrue();
});
