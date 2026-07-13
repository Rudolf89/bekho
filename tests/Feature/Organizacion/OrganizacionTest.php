<?php

use App\Livewire\Academias\GestionAcademias;
use App\Livewire\Sedes\GestionSedes;
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
    $this->bekho = Academia::where('nombre', 'BEKHO')->first();
});

function actorOrg(string $rol, ?int $academiaId): User
{
    $exige2fa = in_array($rol, config('bekho.2fa_obligatorio_para', []), true);
    $user = User::factory()->create([
        'academia_id' => $academiaId,
        'two_factor_confirmed_at' => $exige2fa ? now() : null,
    ]);
    $user->assignRole($rol);

    return $user;
}

// --- Permisos ----------------------------------------------------------------

test('un maestro gestiona sedes pero no academias', function () {
    $user = actorOrg('maestro', $this->bekho->id);

    Tenant::olvidar();
    $this->actingAs($user)->get(route('sedes.index'))->assertOk();
    $this->actingAs($user)->get(route('academias.index'))->assertForbidden();
});

test('un instructor no gestiona sedes', function () {
    $user = actorOrg('instructor', $this->bekho->id);

    $this->actingAs($user)->get(route('sedes.index'))->assertForbidden();
});

test('el super-admin gestiona academias', function () {
    $user = actorOrg('super-admin', null);

    $this->actingAs($user)->get(route('academias.index'))->assertOk();
});

// --- Sedes: scope y creación -------------------------------------------------

test('las sedes se aíslan por academia', function () {
    $otra = Academia::create(['nombre' => 'OTRA', 'activo' => true]);
    Sede::create(['academia_id' => $this->bekho->id, 'nombre' => 'De BEKHO', 'activo' => true]);
    Sede::create(['academia_id' => $otra->id, 'nombre' => 'De OTRA', 'activo' => true]);

    Tenant::set($this->bekho->id);
    expect(Sede::count())->toBe(1);
    expect(Sede::first()->nombre)->toBe('De BEKHO');
});

test('un maestro crea una sede en su propia academia', function () {
    $maestro = actorOrg('maestro', $this->bekho->id);

    Livewire::actingAs($maestro)->test(GestionSedes::class)
        ->call('nueva')
        ->set('nombre', 'Sede Nueva')
        ->set('comuna', 'Ñuñoa')
        ->call('guardar')
        ->assertHasNoErrors();

    $sede = Sede::sinAcademia()->where('nombre', 'Sede Nueva')->first();
    expect($sede)->not->toBeNull();
    expect($sede->academia_id)->toBe($this->bekho->id);
});

// --- Academias: creación -----------------------------------------------------

test('el super-admin crea una academia nueva', function () {
    $admin = actorOrg('super-admin', null);

    Livewire::actingAs($admin)->test(GestionAcademias::class)
        ->call('nueva')
        ->set('nombre', 'Academia POWER')
        ->set('email', 'power@bekho.cl')
        ->call('guardar')
        ->assertHasNoErrors();

    expect(Academia::where('nombre', 'Academia POWER')->exists())->toBeTrue();
});
