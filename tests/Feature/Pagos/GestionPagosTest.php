<?php

use App\Livewire\Pagos\GestionPagos;
use App\Models\Cargo;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Pago;
use App\Models\Persona;
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
    Tenant::set($this->bekho->id);

    $this->direccion = User::factory()->create(['grupo_id' => $this->bekho->id, 'two_factor_confirmed_at' => now()]);
    $this->direccion->assignRole('direccion');

    $this->mensualidad = TipoCargo::where('recurrente', true)->orderBy('orden')->first();

    $ana = Persona::create(['nombres' => 'Ana', 'apellido_paterno' => 'Pérez', 'fecha_nacimiento' => now()->subYears(10)]);
    $beto = Persona::create(['nombres' => 'Beto', 'apellido_paterno' => 'Soto', 'fecha_nacimiento' => now()->subYears(10)]);

    $this->matAna = Matricula::create(['grupo_id' => $this->bekho->id, 'persona_id' => $ana->id, 'grupo_etario' => 'for_kids', 'estado' => 'activa', 'fecha_ingreso' => now()]);
    $this->matBeto = Matricula::create(['grupo_id' => $this->bekho->id, 'persona_id' => $beto->id, 'grupo_etario' => 'for_kids', 'estado' => 'activa', 'fecha_ingreso' => now()]);

    // Cargos pendientes y pagos verificados que los cubren.
    foreach ([[$this->matAna, 30000], [$this->matBeto, 50000]] as [$mat, $monto]) {
        Cargo::withoutGlobalScopes()->create([
            'grupo_id' => $this->bekho->id, 'matricula_id' => $mat->id, 'tipo_cargo_id' => $this->mensualidad->id,
            'periodo' => now()->startOfMonth(), 'monto' => $monto, 'estado' => 'pendiente',
        ]);
    }

    Livewire::actingAs($this->direccion);
    app(ServicioPagos::class)->registrarPago($this->matAna, 30000, now());
    app(ServicioPagos::class)->registrarPago($this->matBeto, 50000, now());
});

test('la tabla de pagos suma el total verificado del filtro', function () {
    Livewire::actingAs($this->direccion)->test(GestionPagos::class)
        ->assertViewHas('sumaPagos', 80000)
        ->assertViewHas('totalPagos', 2);
});

test('el buscador filtra los pagos por quien pagó y recalcula la suma', function () {
    Livewire::actingAs($this->direccion)->test(GestionPagos::class)
        ->set('buscar', 'Ana')
        ->assertViewHas('totalPagos', 1)
        ->assertViewHas('sumaPagos', 30000);
});

test('el filtro por estado acota a los pagos de ese estado', function () {
    Livewire::actingAs($this->direccion)->test(GestionPagos::class)
        ->set('filtroEstado', 'por_verificar')
        ->assertViewHas('totalPagos', 0);
});

test('se puede registrar un pago que salda el cargo de la matrícula', function () {
    $ceci = Persona::create(['nombres' => 'Ceci', 'apellido_paterno' => 'Díaz', 'fecha_nacimiento' => now()->subYears(9)]);
    $matCeci = Matricula::create(['grupo_id' => $this->bekho->id, 'persona_id' => $ceci->id, 'grupo_etario' => 'for_kids', 'estado' => 'activa', 'fecha_ingreso' => now()]);
    $cargo = Cargo::withoutGlobalScopes()->create([
        'grupo_id' => $this->bekho->id, 'matricula_id' => $matCeci->id, 'tipo_cargo_id' => $this->mensualidad->id,
        'periodo' => now()->startOfMonth(), 'monto' => 40000, 'estado' => 'pendiente',
    ]);

    Livewire::actingAs($this->direccion)->test(GestionPagos::class)
        ->call('abrirRegistro', $matCeci->id)
        ->assertSet('pagoMatriculaId', (string) $matCeci->id)
        ->set('pagoMonto', 40000)
        ->call('registrarPago')
        ->assertHasNoErrors();

    expect(Pago::where('pagado_por_persona_id', $ceci->id)->where('estado', 'verificado')->exists())->toBeTrue()
        ->and($cargo->fresh()->estado->value)->toBe('pagado');
});
