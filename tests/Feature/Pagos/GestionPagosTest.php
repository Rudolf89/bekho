<?php

use App\Livewire\Pagos\GestionPagos;
use App\Models\Estudiante;
use App\Models\Grupo;
use App\Models\Pago;
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

    $this->ana = Estudiante::create(['grupo_id' => $this->bekho->id, 'nombre' => 'Ana Pérez', 'grupo_etario' => 'for_kids', 'activo' => true]);
    $this->beto = Estudiante::create(['grupo_id' => $this->bekho->id, 'nombre' => 'Beto Soto', 'grupo_etario' => 'for_kids', 'activo' => true]);

    Pago::create(['grupo_id' => $this->bekho->id, 'estudiante_id' => $this->ana->id, 'tipo' => 'mensualidad', 'periodo' => now()->startOfMonth(), 'monto' => 30000, 'fecha_pago' => now()]);
    Pago::create(['grupo_id' => $this->bekho->id, 'estudiante_id' => $this->beto->id, 'tipo' => 'matricula', 'periodo' => now()->startOfMonth(), 'monto' => 50000, 'fecha_pago' => now()]);
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
