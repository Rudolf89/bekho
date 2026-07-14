<?php

use App\Livewire\Clases\GestionClases;
use App\Livewire\Sedes\GestionSedes;
use App\Models\Academia;
use App\Models\Clase;
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
    Tenant::set($this->bekho->id);

    $this->sede = Sede::create(['academia_id' => $this->bekho->id, 'nombre' => 'Central', 'activo' => true]);
    $this->direccion = User::factory()->create([
        'academia_id' => $this->bekho->id, 'two_factor_confirmed_at' => now(),
    ]);
    $this->direccion->assignRole('direccion');
});

test('una clase puede tener varios instructores con su papel', function () {
    $titular = User::factory()->create(['academia_id' => $this->bekho->id]);
    $titular->assignRole('instructor');
    $ayudante = User::factory()->create(['academia_id' => $this->bekho->id]);
    $ayudante->assignRole('instructor');

    Livewire::actingAs($this->direccion)->test(GestionClases::class)
        ->call('nuevo')
        ->set('nombre', 'Kids')
        ->set('sede_id', (string) $this->sede->id)
        ->set('grupo_etario', 'for_kids')
        ->set('dia_semana', '1')
        ->set('hora_inicio', '10:00')
        ->set('asignaciones', [
            ['user_id' => (string) $titular->id, 'papel' => 'titular'],
            ['user_id' => (string) $ayudante->id, 'papel' => 'ayudante'],
        ])
        ->call('guardar')
        ->assertHasNoErrors();

    $clase = Clase::where('nombre', 'Kids')->first();

    expect($clase->instructores()->count())->toBe(2)
        // El titular se refleja en la columna heredada instructor_id.
        ->and($clase->instructor_id)->toBe($titular->id);

    $papeles = $clase->instructores()->pluck('papel', 'users.id');
    expect($papeles[$titular->id])->toBe('titular')
        ->and($papeles[$ayudante->id])->toBe('ayudante');
});

test('el nombre de la clase se autocompleta con grupo, día, hora y sede', function () {
    Livewire::actingAs($this->direccion)->test(GestionClases::class)
        ->call('nuevo')
        ->set('grupo_etario', 'tigers')
        ->set('dia_semana', '1')
        ->set('hora_inicio', '18:10')
        ->set('sede_id', (string) $this->sede->id)
        ->assertSet('nombre', 'Tigers · Lunes 18:10 · Central');
});

test('la hora de fin se autocompleta a inicio + 45 min pero es editable', function () {
    Livewire::actingAs($this->direccion)->test(GestionClases::class)
        ->call('nuevo')
        ->set('hora_inicio', '18:10')
        ->assertSet('hora_fin', '18:55')
        // El usuario la puede cambiar y no se vuelve a sobrescribir.
        ->set('hora_fin', '20:00')
        ->set('hora_inicio', '19:00')
        ->assertSet('hora_fin', '20:00');
});

test('si el usuario escribe un nombre, deja de autocompletarse', function () {
    Livewire::actingAs($this->direccion)->test(GestionClases::class)
        ->call('nuevo')
        ->set('grupo_etario', 'tigers')
        ->set('nombre', 'Clase de los pequeños')
        ->set('dia_semana', '1')
        ->assertSet('nombre', 'Clase de los pequeños');
});

test('la federación es de solo lectura: no puede crear una sede', function () {
    $federacion = User::factory()->create(['academia_id' => null]);
    $federacion->assignRole('federacion');

    // La federación ve todo (no filtra lecturas) pero no escribe.
    Tenant::set($this->bekho->id, filtraLecturas: false);

    Livewire::actingAs($federacion)->test(GestionSedes::class)
        ->call('nueva')
        ->set('nombre', 'No debería crearse')
        ->set('academia_id', (string) $this->bekho->id)
        ->call('guardar')
        ->assertForbidden();

    expect(Sede::sinAcademia()->where('nombre', 'No debería crearse')->exists())->toBeFalse();
});

test('una persona puede estar a cargo de varias sedes (pivote sede_user)', function () {
    $sedeB = Sede::create(['academia_id' => $this->bekho->id, 'nombre' => 'Sur', 'activo' => true]);

    $instructor = User::factory()->create(['academia_id' => $this->bekho->id]);
    $instructor->assignRole('instructor');
    $instructor->sedes()->sync([$this->sede->id, $sedeB->id]);

    expect($instructor->sedes()->pluck('nombre')->sort()->values()->all())
        ->toBe(['Central', 'Sur']);
    expect($this->sede->instructores()->whereKey($instructor->id)->exists())->toBeTrue();
    expect($sedeB->instructores()->whereKey($instructor->id)->exists())->toBeTrue();
});

test('la federación accede al listado de sedes de todas las academias', function () {
    $otra = Academia::create(['nombre' => 'Otro Grupo', 'activo' => true]);
    Sede::create(['academia_id' => $otra->id, 'nombre' => 'Sede Ajena', 'activo' => true]);

    $federacion = User::factory()->create(['academia_id' => null]);
    $federacion->assignRole('federacion');

    Tenant::olvidar();
    $this->actingAs($federacion)->get(route('sedes.index'))
        ->assertOk()
        ->assertSee('Central')
        ->assertSee('Sede Ajena');
});
