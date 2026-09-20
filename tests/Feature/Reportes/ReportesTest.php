<?php

use App\Livewire\Reportes\Reportes;
use App\Models\Cargo;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Persona;
use App\Models\Sede;
use App\Models\TipoCargo;
use App\Models\User;
use App\Support\Tenancy\Grupo as Tenant;
use Database\Seeders\CatalogosFederacionSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    $this->seed(CatalogosFederacionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->bekho = Grupo::where('nombre', 'BEKHO Power Academy')->first();
    $this->sede = Sede::create(['grupo_id' => $this->bekho->id, 'nombre' => 'Central', 'activo' => true]);
    Tenant::set($this->bekho->id);
});

afterEach(fn () => Tenant::olvidar());

function actorReportes(string $rol): User
{
    $exige2fa = in_array($rol, config('bekho.2fa_obligatorio_para', []), true);
    $u = User::factory()->create(['grupo_id' => 1, 'two_factor_confirmed_at' => $exige2fa ? now() : null]);
    $u->assignRole($rol);

    return $u;
}

function matriculaReporte(int $grupoId, int $sedeId, ?int $gradoId): Matricula
{
    $persona = Persona::create(['nombres' => 'Alumno '.uniqid(), 'fecha_nacimiento' => now()->subYears(12), 'grado_id' => $gradoId]);

    return Matricula::create([
        'grupo_id' => $grupoId, 'persona_id' => $persona->id, 'sede_id' => $sedeId,
        'grupo_etario' => 'for_kids', 'estado' => 'activa', 'fecha_ingreso' => now(),
    ]);
}

test('la dirección accede a reportes; el instructor no', function () {
    Tenant::olvidar();
    $this->actingAs(actorReportes('direccion'))->get(route('reportes.index'))->assertOk();
    $this->actingAs(actorReportes('instructor'))->get(route('reportes.index'))->assertForbidden();
});

test('los reportes resumen la distribución y la comparativa', function () {
    $blanco = Grado::create(['nombre' => 'Blanco', 'orden' => 1, 'escala' => 'adultos', 'color' => 'Blanco', 'activo' => true]);
    matriculaReporte($this->bekho->id, $this->sede->id, $blanco->id);
    matriculaReporte($this->bekho->id, $this->sede->id, $blanco->id);

    Livewire::actingAs(actorReportes('direccion'))->test(Reportes::class)
        ->assertViewHas('alumnosActivos', 2)
        ->assertViewHas('distribucion', fn ($d) => $d->firstWhere('nombre', 'Blanco')['total'] === 2)
        ->assertViewHas('comparativa', fn ($c) => $c->firstWhere('sede', 'Central')['activos'] === 2)
        ->assertSee('Comparativa de sedes');
});

test('el cobrado del mes suma los cargos pagados de la sede', function () {
    $mensualidad = TipoCargo::where('recurrente', true)->orderBy('orden')->first();
    $matricula = matriculaReporte($this->bekho->id, $this->sede->id, null);
    Cargo::create([
        'grupo_id' => $this->bekho->id, 'matricula_id' => $matricula->id, 'sede_id' => $this->sede->id,
        'tipo_cargo_id' => $mensualidad->id, 'periodo' => now()->startOfMonth(), 'monto' => 30000, 'estado' => 'pagado',
    ]);

    Livewire::actingAs(actorReportes('direccion'))->test(Reportes::class)
        ->assertViewHas('comparativa', fn ($c) => $c->firstWhere('sede', 'Central')['cobrado'] === 30000);
});

test('la comparativa se exporta a CSV', function () {
    matriculaReporte($this->bekho->id, $this->sede->id, null);

    Livewire::actingAs(actorReportes('direccion'))->test(Reportes::class)
        ->call('exportarCsv')
        ->assertFileDownloaded();
});
