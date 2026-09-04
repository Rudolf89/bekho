<?php

use App\Models\Academia;
use App\Models\User;
use App\Support\Tenancy\Academia as Tenant;
use Database\Seeders\AcademiasBekhoSeeder;
use Database\Seeders\CargosRangosSeeder;
use Database\Seeders\LineaSupervisionSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(CargosRangosSeeder::class);
    $this->seed(RolesPermisosSeeder::class);
    $this->seed(LineaSupervisionSeeder::class);
    $this->seed(AcademiasBekhoSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Tenant::olvidar();
});

test('se siembran las academias de la federación con sus sedes', function () {
    expect(Academia::where('nombre', 'Academia Oriente')->exists())->toBeTrue()
        ->and(Academia::where('nombre', 'ATA BEKHO Pride')->exists())->toBeTrue()
        ->and(Academia::where('nombre', 'ATA BEKHO IV Región')->exists())->toBeTrue();

    $pride = Academia::where('nombre', 'ATA BEKHO Pride')->first();
    expect($pride->sedes()->count())->toBe(4)
        ->and($pride->sedes()->pluck('nombre')->all())->toContain('Maipú', 'Peñaflor');
});

test('la BEKHO Power Academy existente no se duplica', function () {
    expect(Academia::where('nombre', 'BEKHO Power Academy')->count())->toBe(1);
});

test('los usuarios de un campo con base quedan enlazados a su academia', function () {
    $pride = Academia::where('nombre', 'ATA BEKHO Pride')->first();
    $nogues = User::sinAcademia()->get()
        ->first(fn (User $u) => Str::of($u->name)->ascii()->lower()->squish()->contains('cristian nogues'));

    expect($nogues->academia_id)->toBe($pride->id);

    // Un miembro del campo 116 también queda en Pride.
    $miembro = User::where('academia_id', $pride->id)->where('name', 'like', '%ARACELI SOTO%')->first();
    expect($miembro)->not->toBeNull();
});

test('un campo sin base no fija academia a sus usuarios', function () {
    // Campo 119 (Herman Bastias) no está enlazado a ninguna academia.
    $bastias = User::sinAcademia()->get()
        ->first(fn (User $u) => Str::of($u->name)->ascii()->lower()->squish()->contains('herman bastias'));

    expect($bastias->academia_id)->toBeNull();
});

test('las academias sin campo no enlazan usuarios pero sí crean sus sedes', function () {
    $ivRegion = Academia::where('nombre', 'ATA BEKHO IV Región')->first();

    expect($ivRegion->usuarios()->count())->toBe(0)
        ->and($ivRegion->sedes()->count())->toBe(1);
});
