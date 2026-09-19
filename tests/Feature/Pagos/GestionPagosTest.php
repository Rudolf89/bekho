<?php

use App\Livewire\Pagos\GestionPagos;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Pago;
use App\Models\Persona;
use App\Models\User;
use App\Support\Tenancy\Grupo as Tenant;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->bekho = Grupo::where('nombre', 'BEKHO Power Academy')->first();
    Tenant::set($this->bekho->id);

    $this->direccion = User::factory()->create(['grupo_id' => $this->bekho->id, 'two_factor_confirmed_at' => now()]);
    $this->direccion->assignRole('direccion');

    $ana = Persona::create(['nombres' => 'Ana', 'apellido_paterno' => 'Pérez', 'fecha_nacimiento' => now()->subYears(10)]);
    $beto = Persona::create(['nombres' => 'Beto', 'apellido_paterno' => 'Soto', 'fecha_nacimiento' => now()->subYears(10)]);

    $this->matAna = Matricula::create(['grupo_id' => $this->bekho->id, 'persona_id' => $ana->id, 'grupo_etario' => 'for_kids', 'estado' => 'activa', 'fecha_ingreso' => now()]);
    $this->matBeto = Matricula::create(['grupo_id' => $this->bekho->id, 'persona_id' => $beto->id, 'grupo_etario' => 'for_kids', 'estado' => 'activa', 'fecha_ingreso' => now()]);

    Pago::create(['grupo_id' => $this->bekho->id, 'matricula_id' => $this->matAna->id, 'tipo' => 'mensualidad', 'periodo' => now()->startOfMonth(), 'monto' => 30000, 'fecha_pago' => now()]);
    Pago::create(['grupo_id' => $this->bekho->id, 'matricula_id' => $this->matBeto->id, 'tipo' => 'matricula', 'periodo' => now()->startOfMonth(), 'monto' => 50000, 'fecha_pago' => now()]);
});

test('la tabla de pagos suma el total recaudado del filtro', function () {
    Livewire::actingAs($this->direccion)->test(GestionPagos::class)
        ->assertViewHas('sumaPagos', 80000)
        ->assertViewHas('totalPagos', 2);
});

test('el buscador filtra los pagos por alumno y recalcula la suma', function () {
    Livewire::actingAs($this->direccion)->test(GestionPagos::class)
        ->set('buscar', 'Ana')
        ->assertViewHas('totalPagos', 1)
        ->assertViewHas('sumaPagos', 30000);
});

test('el filtro por tipo acota la suma a ese tipo', function () {
    Livewire::actingAs($this->direccion)->test(GestionPagos::class)
        ->set('filtroTipo', 'matricula')
        ->assertViewHas('totalPagos', 1)
        ->assertViewHas('sumaPagos', 50000);
});

test('se puede registrar un pago para una matrícula', function () {
    Livewire::actingAs($this->direccion)->test(GestionPagos::class)
        ->call('abrirRegistro', $this->matAna->id)
        ->assertSet('pagoMatriculaId', (string) $this->matAna->id)
        ->set('pagoTipo', 'matricula')
        ->set('pagoMonto', 50000)
        ->call('registrarPago')
        ->assertHasNoErrors();

    expect(Pago::where('matricula_id', $this->matAna->id)->where('tipo', 'matricula')->exists())->toBeTrue();
});
