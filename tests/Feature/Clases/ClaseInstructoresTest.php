<?php

use App\Livewire\Clases\GestionClases;
use App\Livewire\Sedes\GestionSedes;
use App\Models\Clase;
use App\Models\Grupo;
use App\Models\Sede;
use App\Models\User;
use App\Support\Tenancy\Grupo as Tenant;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->bekho = Grupo::where('nombre', 'BEKHO Power Academy')->first();
    Tenant::set($this->bekho->id);

    $this->sede = Sede::create(['grupo_id' => $this->bekho->id, 'nombre' => 'Central', 'activo' => true]);
    $this->direccion = User::factory()->create([
        'grupo_id' => $this->bekho->id, 'two_factor_confirmed_at' => now(),
    ]);
    $this->direccion->assignRole('direccion');
});

test('una clase puede tener varios instructores con su papel', function () {
    $titular = User::factory()->create(['grupo_id' => $this->bekho->id]);
    $titular->assignRole('instructor');
    $ayudante = User::factory()->create(['grupo_id' => $this->bekho->id]);
    $ayudante->assignRole('instructor');

    Livewire::actingAs($this->direccion)->test(GestionClases::class)
        ->call('nuevo')
        ->set('nombre', 'Kids')
        ->set('sede_id', (string) $this->sede->id)
        ->set('grupo_etario', 'for_kids')
        ->set('horarios', [
            ['dia_semana' => '1', 'hora_inicio' => '10:00', 'hora_fin' => '10:45'],
        ])
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

test('el nombre de la clase se autocompleta con grupo y sede', function () {
    Livewire::actingAs($this->direccion)->test(GestionClases::class)
        ->call('nuevo')
        ->set('grupo_etario', 'tigers')
        ->set('sede_id', (string) $this->sede->id)
        ->assertSet('nombre', 'Tigers · Central');
});

test('la hora de fin de un horario se autocompleta a inicio + 45 min pero es editable', function () {
    Livewire::actingAs($this->direccion)->test(GestionClases::class)
        ->call('nuevo') // arranca con un horario vacío (índice 0)
        ->set('horarios.0.hora_inicio', '18:10')
        ->assertSet('horarios.0.hora_fin', '18:55')
        // El usuario la puede cambiar y no se vuelve a sobrescribir.
        ->set('horarios.0.hora_fin', '20:00')
        ->set('horarios.0.hora_inicio', '19:00')
        ->assertSet('horarios.0.hora_fin', '20:00');
});

test('una clase puede tener varios horarios (lunes y miércoles)', function () {
    Livewire::actingAs($this->direccion)->test(GestionClases::class)
        ->call('nuevo')
        ->set('nombre', 'Kids')
        ->set('sede_id', (string) $this->sede->id)
        ->set('grupo_etario', 'for_kids')
        ->set('horarios', [
            ['dia_semana' => '1', 'hora_inicio' => '18:00', 'hora_fin' => '18:45'],
            ['dia_semana' => '3', 'hora_inicio' => '18:00', 'hora_fin' => '18:45'],
        ])
        ->call('guardar')
        ->assertHasNoErrors();

    $clase = Clase::where('nombre', 'Kids')->first();

    expect($clase->horarios()->count())->toBe(2)
        ->and($clase->horarios->map(fn ($h) => $h->dia_semana->value)->sort()->values()->all())->toBe([1, 3]);
});

test('una clase exige al menos un horario', function () {
    Livewire::actingAs($this->direccion)->test(GestionClases::class)
        ->call('nuevo')
        ->set('nombre', 'Sin horario')
        ->set('sede_id', (string) $this->sede->id)
        ->set('grupo_etario', 'for_kids')
        ->set('horarios', [])
        ->call('guardar')
        ->assertHasErrors('horarios');

    expect(Clase::where('nombre', 'Sin horario')->exists())->toBeFalse();
});

test('si el usuario escribe un nombre, deja de autocompletarse', function () {
    Livewire::actingAs($this->direccion)->test(GestionClases::class)
        ->call('nuevo')
        ->set('grupo_etario', 'tigers')
        ->set('nombre', 'Clase de los pequeños')
        ->set('sede_id', (string) $this->sede->id)
        ->assertSet('nombre', 'Clase de los pequeños');
});

test('la federación es de solo lectura: no puede crear una sede', function () {
    $federacion = User::factory()->create(['grupo_id' => null]);
    $federacion->assignRole('federacion');

    // La federación ve todo (no filtra lecturas) pero no escribe.
    Tenant::set($this->bekho->id, filtraLecturas: false);

    Livewire::actingAs($federacion)->test(GestionSedes::class)
        ->call('nueva')
        ->set('nombre', 'No debería crearse')
        ->set('grupo_id', (string) $this->bekho->id)
        ->call('guardar')
        ->assertForbidden();

    expect(Sede::sinGrupo()->where('nombre', 'No debería crearse')->exists())->toBeFalse();
});

test('una persona puede estar a cargo de varias sedes (pivote sede_user)', function () {
    $sedeB = Sede::create(['grupo_id' => $this->bekho->id, 'nombre' => 'Sur', 'activo' => true]);

    $instructor = User::factory()->create(['grupo_id' => $this->bekho->id]);
    $instructor->assignRole('instructor');
    $instructor->sedes()->sync([$this->sede->id, $sedeB->id]);

    expect($instructor->sedes()->pluck('nombre')->sort()->values()->all())
        ->toBe(['Central', 'Sur']);
    expect($this->sede->instructores()->whereKey($instructor->id)->exists())->toBeTrue();
    expect($sedeB->instructores()->whereKey($instructor->id)->exists())->toBeTrue();
});

test('la federación accede al listado de sedes de todos los grupos', function () {
    $otra = Grupo::create(['nombre' => 'Otro Grupo', 'activo' => true]);
    Sede::create(['grupo_id' => $otra->id, 'nombre' => 'Sede Ajena', 'activo' => true]);

    $federacion = User::factory()->create(['grupo_id' => null]);
    $federacion->assignRole('federacion');

    Tenant::olvidar();
    $this->actingAs($federacion)->get(route('sedes.index'))
        ->assertOk()
        ->assertSee('Central')
        ->assertSee('Sede Ajena');
});
