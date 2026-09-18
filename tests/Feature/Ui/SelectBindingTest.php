<?php

use App\Livewire\Clases\GestionClases;
use App\Livewire\Estudiantes\GestionEstudiantes;
use App\Livewire\Examenes\GestionConvocatorias;
use App\Livewire\Planillas\GestionPlanillas;
use App\Livewire\Sedes\GestionSedes;
use App\Livewire\Usuarios\GestionUsuarios;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

/*
 * Los <select> de Flux usan un placeholder con value="". Si la propiedad
 * enlazada arranca en null, el navegador muestra la primera opción real sin
 * disparar 'change', y Livewire nunca captura el valor (queda null al enviar).
 * Estas pruebas fijan la invariante: las propiedades de <select> deben arrancar
 * en '' (cadena vacía) para que el placeholder quede seleccionado.
 */

beforeEach(function () {
    foreach (['admin-plataforma', 'direccion', 'instructor', 'apoderado'] as $rol) {
        Role::findOrCreate($rol, 'web');
    }

    $user = User::factory()->create();
    $user->assignRole('admin-plataforma');
    $user->forceFill(['two_factor_confirmed_at' => now()])->save();
    actingAs($user);
});

test('los select de clases arrancan vacíos, no en null', function () {
    Livewire::test(GestionClases::class)
        ->call('nuevo')
        ->assertSet('sede_id', '')
        ->assertSet('grupo_etario', '')
        ->assertSet('dia_semana', '')
        ->assertSet('planilla_id', '');
});

test('los select de estudiantes arrancan vacíos', function () {
    Livewire::test(GestionEstudiantes::class)
        ->call('nuevo')
        ->assertSet('grado_id', '')
        ->assertSet('sede_id', '');
});

test('los select de sedes arrancan vacíos para admin-plataforma', function () {
    Livewire::test(GestionSedes::class)
        ->call('nueva')
        ->assertSet('comuna', '')
        ->assertSet('grupo_id', '');
});

test('los select de planillas arrancan vacíos', function () {
    Livewire::test(GestionPlanillas::class)
        ->call('nueva')
        ->assertSet('programa_id', '')
        ->assertSet('habilidad_vida', '');
});

test('el select de convocatorias arranca vacío', function () {
    Livewire::test(GestionConvocatorias::class)
        ->call('nueva')
        ->assertSet('sede_id', '');
});

test('los select de usuarios arrancan vacíos para admin-plataforma', function () {
    Livewire::test(GestionUsuarios::class)
        ->call('nuevo')
        ->assertSet('rango_id', '')
        ->assertSet('sedes', [])
        ->assertSet('grupo_id', '');
});
