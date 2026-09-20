<?php

use App\Livewire\Cobros\GestionTarifas;
use App\Models\Grupo;
use App\Models\Persona;
use App\Models\PersonalGrupo;
use App\Models\Sede;
use App\Models\TarifaSede;
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
    $this->sedeA = Sede::create(['grupo_id' => $this->bekho->id, 'nombre' => 'Sede Alfa', 'activo' => true]);
    $this->sedeB = Sede::create(['grupo_id' => $this->bekho->id, 'nombre' => 'Sede Beta', 'activo' => true]);
    $this->mensualidad = TipoCargo::where('nombre', 'Mensualidad')->first();
    Tenant::set($this->bekho->id);
});

afterEach(fn () => Tenant::olvidar());

function usuarioTarifas(string $rol): User
{
    $exige2fa = in_array($rol, config('bekho.2fa_obligatorio_para', []), true);
    $u = User::factory()->create(['grupo_id' => 1, 'two_factor_confirmed_at' => $exige2fa ? now() : null]);
    $u->assignRole($rol);

    return $u;
}

test('la dirección accede a tarifas; administración no', function () {
    Tenant::olvidar();
    $this->actingAs(usuarioTarifas('direccion'))->get(route('tarifas.index'))->assertOk();
    $this->actingAs(usuarioTarifas('administrativo'))->get(route('tarifas.index'))->assertForbidden();
});

test('la dirección de grupo ve todas las sedes y crea una tarifa', function () {
    $direccion = usuarioTarifas('direccion');

    Livewire::actingAs($direccion)->test(GestionTarifas::class)
        ->assertSee('Sede Alfa')
        ->assertSee('Sede Beta')
        ->call('abrirNueva', $this->sedeA->id)
        ->set('tarifaTipoId', (string) $this->mensualidad->id)
        ->set('tarifaCantidad', 1)
        ->set('tarifaMonto', 40000)
        ->call('guardarTarifa')
        ->assertHasNoErrors();

    expect(TarifaSede::where('sede_id', $this->sedeA->id)->where('monto_por_alumno', 40000)->exists())->toBeTrue();
});

test('un direccion-sede solo ve y administra su sede', function () {
    $persona = Persona::create(['nombres' => 'Jefe Alfa', 'fecha_nacimiento' => now()->subYears(35)]);
    $user = User::factory()->create(['grupo_id' => $this->bekho->id, 'persona_id' => $persona->id]);
    $user->assignRole('direccion-sede');
    $personal = PersonalGrupo::create(['persona_id' => $persona->id, 'grupo_id' => $this->bekho->id, 'activo' => true]);
    $personal->otorgarRol('direccion-sede', $this->sedeA->id);

    Livewire::actingAs($user)->test(GestionTarifas::class)
        ->assertSee('Sede Alfa')
        ->assertDontSee('Sede Beta');

    // No puede abrir el alta de tarifa en una sede ajena.
    Livewire::actingAs($user)->test(GestionTarifas::class)
        ->call('abrirNueva', $this->sedeB->id)
        ->assertStatus(403);
});
