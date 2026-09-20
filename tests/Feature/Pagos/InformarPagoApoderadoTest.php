<?php

use App\Livewire\Apoderado\MisEstudiantes;
use App\Models\Cargo;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Pago;
use App\Models\Persona;
use App\Models\TipoCargo;
use App\Models\Tutela;
use App\Models\User;
use App\Support\Tenancy\Grupo as Tenant;
use Database\Seeders\CatalogosFederacionSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    $this->seed(CatalogosFederacionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->bekho = Grupo::where('nombre', 'BEKHO Power Academy')->first();
    Tenant::set($this->bekho->id);

    $this->mensualidad = TipoCargo::where('recurrente', true)->orderBy('orden')->first();

    // Apoderado con persona y tutela sobre su hijo (matrícula con cargo pendiente).
    $apoPersona = Persona::create(['nombres' => 'Madre', 'fecha_nacimiento' => now()->subYears(38)]);
    $this->apoderado = User::factory()->create(['grupo_id' => $this->bekho->id, 'persona_id' => $apoPersona->id]);
    $this->apoderado->assignRole('apoderado');

    $hijo = Persona::create(['nombres' => 'Hija', 'fecha_nacimiento' => now()->subYears(9)]);
    $this->matricula = Matricula::create(['grupo_id' => $this->bekho->id, 'persona_id' => $hijo->id, 'grupo_etario' => 'for_kids', 'estado' => 'activa', 'fecha_ingreso' => now()]);
    Tutela::create(['apoderado_persona_id' => $apoPersona->id, 'alumno_persona_id' => $hijo->id, 'parentesco' => 'madre', 'responsable_pago' => true]);

    $this->cargo = Cargo::withoutGlobalScopes()->create([
        'grupo_id' => $this->bekho->id, 'matricula_id' => $this->matricula->id, 'tipo_cargo_id' => $this->mensualidad->id,
        'periodo' => now()->startOfMonth(), 'monto' => 30000, 'estado' => 'pendiente',
    ]);
});

afterEach(fn () => Tenant::olvidar());

test('el apoderado informa un pago que queda por verificar con su comprobante', function () {
    Storage::fake('local');

    Livewire::actingAs($this->apoderado)->test(MisEstudiantes::class)
        ->call('abrirInformar', $this->matricula->id)
        ->set('pagoMonto', 30000)
        ->set('pagoReferencia', 'TX-123')
        ->set('comprobante', UploadedFile::fake()->create('comprobante.pdf', 120, 'application/pdf'))
        ->call('informarPago')
        ->assertHasNoErrors();

    $pago = Pago::withoutGlobalScopes()->first();
    expect($pago)->not->toBeNull()
        ->and($pago->estado->value)->toBe('por_verificar')
        ->and($pago->comprobante_archivo)->not->toBeNull();

    Storage::disk('local')->assertExists($pago->comprobante_archivo);

    // El pago por verificar NO salda el cargo todavía.
    expect($this->cargo->fresh()->estado->value)->toBe('pendiente');
});

test('el apoderado no puede informar un pago de una matrícula ajena', function () {
    $ajena = Persona::create(['nombres' => 'Ajeno', 'fecha_nacimiento' => now()->subYears(10)]);
    $matAjena = Matricula::create(['grupo_id' => $this->bekho->id, 'persona_id' => $ajena->id, 'grupo_etario' => 'for_kids', 'estado' => 'activa', 'fecha_ingreso' => now()]);

    Storage::fake('local');

    // La matrícula ajena no es visible para el apoderado → no puede pagarla.
    expect(fn () => Livewire::actingAs($this->apoderado)->test(MisEstudiantes::class)
        ->call('abrirInformar', $matAjena->id)
        ->set('pagoMonto', 30000)
        ->set('comprobante', UploadedFile::fake()->create('comprobante.pdf', 120, 'application/pdf'))
        ->call('informarPago'))
        ->toThrow(ModelNotFoundException::class);

    expect(Pago::withoutGlobalScopes()->count())->toBe(0);
});
