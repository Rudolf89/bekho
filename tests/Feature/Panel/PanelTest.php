<?php

use App\Models\Academia;
use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->bekho = Academia::where('nombre', 'BEKHO Power Academy')->first();
});

test('el panel (dashboard) renderiza para el admin-plataforma', function () {
    $admin = User::where('email', 'admin@bekho.cl')->first();
    $admin->forceFill(['two_factor_confirmed_at' => now()])->save(); // requisito de 2FA por rol

    $this->actingAs($admin)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Panel del maestro')
        ->assertSee('Resumen');
});

test('el panel renderiza para un maestro con 2FA', function () {
    $maestro = User::factory()->create([
        'academia_id' => $this->bekho->id,
        'two_factor_confirmed_at' => now(),
    ]);
    $maestro->assignRole('direccion');

    $this->actingAs($maestro)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Clases de hoy')
        ->assertSee('Mi progreso de collar');
});
