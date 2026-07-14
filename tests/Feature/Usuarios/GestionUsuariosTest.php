<?php

use App\Livewire\Usuarios\GestionUsuarios;
use App\Models\Academia;
use App\Models\Sede;
use App\Models\User;
use App\Support\Tenancy\Academia as Tenant;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->bekho = Academia::where('nombre', 'BEKHO Power Academy')->first();

    $this->admin = User::factory()->create(['academia_id' => null, 'two_factor_confirmed_at' => now()]);
    $this->admin->assignRole('admin-plataforma');

    Tenant::set($this->bekho->id, filtraLecturas: false);
});

test('se puede crear un usuario con rol dirección sin asignar sede', function () {
    Livewire::actingAs($this->admin)->test(GestionUsuarios::class)
        ->call('nuevo')
        ->set('name', 'Directora')
        ->set('email', 'dir@bekho.cl')
        ->set('rol', 'direccion')
        ->set('academia_id', (string) $this->bekho->id)
        ->set('sede_id', '')
        ->call('guardar')
        ->assertHasNoErrors();

    $user = User::sinAcademia()->where('email', 'dir@bekho.cl')->first();

    expect($user)->not->toBeNull()
        ->and($user->hasRole('direccion'))->toBeTrue()
        ->and($user->sedes()->count())->toBe(0);
});

test('se puede quitar la sede de un usuario existente (dejarla en blanco)', function () {
    $sede = Sede::create(['academia_id' => $this->bekho->id, 'nombre' => 'Central', 'activo' => true]);
    $user = User::factory()->create(['academia_id' => $this->bekho->id]);
    $user->assignRole('direccion');
    $user->sedes()->attach($sede->id);

    Livewire::actingAs($this->admin)->test(GestionUsuarios::class)
        ->call('editar', $user->id)
        ->assertSet('sede_id', (string) $sede->id)
        ->set('sede_id', '')
        ->call('guardar')
        ->assertHasNoErrors();

    expect($user->fresh()->sedes()->count())->toBe(0);
});
