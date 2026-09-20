<?php

use App\Livewire\Usuarios\GestionUsuarios;
use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->admin = User::where('email', 'admin@bekho.cl')->first();
    $this->admin->forceFill(['two_factor_confirmed_at' => now()])->save();
});

test('la tabla de usuarios ordena al hacer click', function () {
    Livewire::actingAs($this->admin)->test(GestionUsuarios::class)
        ->assertOk()
        ->call('ordenarPor', 'email')->assertSet('ordenCampo', 'email')->assertSet('ordenDir', 'asc')
        ->call('ordenarPor', 'email')->assertSet('ordenDir', 'desc');
});

test('la pantalla de pagos ya no revienta para el admin-plataforma', function () {
    $this->actingAs($this->admin)->get(route('pagos.index'))->assertOk();
});
