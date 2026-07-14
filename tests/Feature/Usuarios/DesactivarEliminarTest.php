<?php

use App\Livewire\Usuarios\GestionUsuarios;
use App\Models\Academia;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Role::findOrCreate('admin-plataforma', 'web');
});

/*
 * Bloqueo de acceso para cuentas desactivadas.
 */

test('un usuario desactivado no puede iniciar sesión', function () {
    $user = User::factory()->create(['activo' => false]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('un usuario activo sí puede iniciar sesión', function () {
    $user = User::factory()->create(['activo' => true]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasNoErrors();

    $this->assertAuthenticated();
});

test('un usuario desactivado durante su sesión es expulsado', function () {
    $user = User::factory()->create(['activo' => true]);

    actingAs($user);

    // Se le desactiva mientras tiene la sesión abierta.
    $user->update(['activo' => false]);

    $this->get(route('dashboard'))->assertRedirect(route('login'));

    $this->assertGuest();
});

/*
 * Eliminación protegida de usuarios.
 */

function adminAutenticado(): User
{
    $admin = User::factory()->create();
    $admin->assignRole('admin-plataforma');
    actingAs($admin);

    return $admin;
}

test('no se puede eliminar un usuario con historial; solo desactivar', function () {
    adminAutenticado();

    $conHistorial = User::factory()->create();
    $academia = Academia::create(['nombre' => 'ATA', 'activo' => true]);

    // Historial: un estudiante vinculado a esa cuenta.
    DB::table('estudiantes')->insert([
        'academia_id' => $academia->id,
        'user_id' => $conHistorial->id,
        'nombre' => 'Alumno vinculado',
        'grupo_etario' => 'tigers',
        'nivel' => 'principiante',
        'activo' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Livewire::test(GestionUsuarios::class)
        ->call('confirmarEliminar', $conHistorial->id)
        ->assertSet('mostrarEliminar', false);

    expect(User::find($conHistorial->id))->not->toBeNull();
});

test('se puede eliminar un usuario sin historial', function () {
    adminAutenticado();

    $limpio = User::factory()->create();

    Livewire::test(GestionUsuarios::class)
        ->call('confirmarEliminar', $limpio->id)
        ->assertSet('mostrarEliminar', true)
        ->call('eliminar')
        ->assertSet('mostrarEliminar', false);

    expect(User::find($limpio->id))->toBeNull();
});

test('no puedes eliminar tu propia cuenta', function () {
    $admin = adminAutenticado();

    Livewire::test(GestionUsuarios::class)
        ->call('confirmarEliminar', $admin->id)
        ->assertSet('mostrarEliminar', false);

    expect(User::find($admin->id))->not->toBeNull();
});
