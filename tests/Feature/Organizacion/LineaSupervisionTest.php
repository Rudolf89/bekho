<?php

use App\Models\CargoRango;
use App\Models\User;
use App\Services\ServicioExamenes;
use App\Support\Tenancy\Academia as Tenant;
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
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    // La línea vive a nivel federación (academia_id null) → sin tenant activo.
    Tenant::olvidar();

    $this->campos = json_decode(
        (string) file_get_contents(database_path('data/linea_supervision.json')),
        true,
    );
});

function usuarioPorNombre(string $nombre): ?User
{
    $clave = Str::of($nombre)->ascii()->lower()->squish()->value();

    return User::sinAcademia()->get()
        ->first(fn (User $u) => Str::of($u->name)->ascii()->lower()->squish()->value() === $clave);
}

test('cada miembro cuelga de su supervisor de campo', function () {
    $sotomayor = usuarioPorNombre('CHIEF MASTER SOTOMAYOR');
    $franco = usuarioPorNombre('SR. FRANCO POBLETE');

    expect($sotomayor)->not->toBeNull()
        ->and($franco)->not->toBeNull()
        ->and($franco->supervisor_id)->toBe($sotomayor->id)
        ->and($franco->supervisor->name)->toBe('CHIEF MASTER SOTOMAYOR');
});

test('los 19 supervisores son raíces (sin supervisor)', function () {
    foreach ($this->campos as $campo) {
        $sup = usuarioPorNombre($campo['supervisor']);
        expect($sup)->not->toBeNull()
            ->and($sup->supervisor_id)->toBeNull();
    }
});

test('la línea descendente del supervisor incluye a todos sus miembros', function () {
    $sotomayor = usuarioPorNombre('CHIEF MASTER SOTOMAYOR');
    $linea = app(ServicioExamenes::class)->lineaDescendente($sotomayor);

    $campo = collect($this->campos)->firstWhere('campo', 108);
    foreach ($campo['miembros'] as $nombre) {
        expect($linea)->toContain(usuarioPorNombre($nombre)->id);
    }
});

test('todos los usuarios de la línea tienen el rol instructor', function () {
    $sotomayor = usuarioPorNombre('CHIEF MASTER SOTOMAYOR');
    $franco = usuarioPorNombre('SR. FRANCO POBLETE');

    expect($sotomayor->hasRole('instructor'))->toBeTrue()
        ->and($franco->hasRole('instructor'))->toBeTrue();
});

test('el rango se deduce del tratamiento marcial', function () {
    $jefe = CargoRango::where('nombre', 'Maestro Jefe')->first();
    $senior = CargoRango::where('nombre', 'Maestro Sénior')->first();
    $maestro = CargoRango::where('nombre', 'Maestro')->first();

    expect(usuarioPorNombre('CHIEF MASTER SOTOMAYOR')->rango_id)->toBe($jefe->id)
        ->and(usuarioPorNombre('SENIOR MASTER VICTOR RODRIGUEZ')->rango_id)->toBe($senior->id)
        ->and(usuarioPorNombre('MASTER LUIS VILLANUEVA')->rango_id)->toBe($maestro->id)
        // SR./SRTA. no fija rango.
        ->and(usuarioPorNombre('SR. FRANCO POBLETE')->rango_id)->toBeNull();
});

test('una persona en varios campos se crea una sola vez (gana la primera aparición)', function () {
    // GERALD PEREZ aparece en el campo 134 (Victor Rodríguez) y en el 111
    // (Pablo Martínez). Gana el 134 por orden de archivo.
    $geralds = User::sinAcademia()->get()
        ->filter(fn (User $u) => Str::of($u->name)->ascii()->lower()->squish()->contains('gerald perez'));

    $victor = usuarioPorNombre('SENIOR MASTER VICTOR RODRIGUEZ');

    expect($geralds)->toHaveCount(1)
        ->and($geralds->first()->supervisor_id)->toBe($victor->id);
});

test('se siembra el árbol completo sin duplicar personas', function () {
    // Total esperado = personas únicas (supervisores + miembros dedup por nombre).
    $unicos = collect($this->campos)
        ->flatMap(fn ($c) => array_merge([$c['supervisor']], $c['miembros']))
        ->map(fn ($n) => Str::of($n)->ascii()->lower()->squish()->value())
        ->unique();

    // +1 por el admin de plataforma que siembra RolesPermisosSeeder.
    expect(User::sinAcademia()->count())->toBe($unicos->count() + 1);
});
