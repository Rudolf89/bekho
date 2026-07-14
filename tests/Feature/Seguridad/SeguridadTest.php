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

/**
 * Crea un usuario con rol, academia y estado de 2FA dados.
 */
function usuario(string $rol, ?int $academiaId, bool $con2fa = false): User
{
    $user = User::factory()->create([
        'academia_id' => $academiaId,
        'two_factor_confirmed_at' => $con2fa ? now() : null,
    ]);
    $user->assignRole($rol);

    return $user;
}

// --- 1. Auto-registro cerrado -------------------------------------------------

test('la ruta de registro ya no está disponible', function () {
    $this->get('/register')->assertNotFound();
});

// --- 2. Gestión de usuarios protegida por permiso ----------------------------

test('un usuario sin permiso gestionar usuarios no accede a la gestión', function () {
    // instructor no tiene "gestionar usuarios" y tampoco requiere 2FA.
    $user = usuario('instructor', $this->bekho->id);

    $this->actingAs($user)
        ->get(route('usuarios.index'))
        ->assertForbidden();
});

test('un maestro con 2FA y permiso accede a la gestión de usuarios', function () {
    $user = usuario('direccion', $this->bekho->id, con2fa: true);

    $this->actingAs($user)
        ->get(route('usuarios.index'))
        ->assertOk();
});

// --- 3. 2FA obligatoria por rol ----------------------------------------------

test('un maestro sin 2FA confirmada es redirigido a la pantalla de seguridad', function () {
    $user = usuario('direccion', $this->bekho->id, con2fa: false);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('security.edit'));
});

test('un maestro con 2FA confirmada pasa sin redirección', function () {
    $user = usuario('direccion', $this->bekho->id, con2fa: true);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});

test('el admin-plataforma sin 2FA también es redirigido a seguridad', function () {
    $user = usuario('admin-plataforma', null, con2fa: false);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('security.edit'));
});

// --- 4. La 2FA NO es obligatoria para roles sin privilegios -------------------

test('un alumno sin 2FA NO es redirigido', function () {
    $user = usuario('alumno', $this->bekho->id, con2fa: false);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});

test('un apoderado sin 2FA NO es redirigido', function () {
    $user = usuario('apoderado', $this->bekho->id, con2fa: false);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});

// --- La pantalla de seguridad no entra en bucle de redirección ---------------

test('un maestro sin 2FA no entra en bucle al abrir seguridad', function () {
    $user = usuario('direccion', $this->bekho->id, con2fa: false);

    // security.edit está exenta de la exigencia de 2FA. Puede redirigir a la
    // confirmación de contraseña (comportamiento normal de Fortify), pero NUNCA
    // debe redirigir de vuelta a security.edit (eso sería el bucle).
    $respuesta = $this->actingAs($user)->get(route('security.edit'));

    expect($respuesta->headers->get('Location'))->not->toBe(route('security.edit'));
});
