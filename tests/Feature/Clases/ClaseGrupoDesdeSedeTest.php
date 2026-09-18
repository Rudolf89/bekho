<?php

use App\Livewire\Clases\GestionClases;
use App\Models\Clase;
use App\Models\Grupo;
use App\Models\Sede;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    foreach (['admin-plataforma', 'direccion', 'instructor'] as $rol) {
        Role::findOrCreate($rol, 'web');
    }
});

test('el admin-plataforma (sin grupo) crea una clase y el grupo se toma de la sede', function () {
    $admin = User::factory()->create(['grupo_id' => null]);
    $admin->assignRole('admin-plataforma');
    actingAs($admin);

    $grupo = Grupo::create(['nombre' => 'ATA', 'activo' => true]);
    $sede = Sede::create(['grupo_id' => $grupo->id, 'nombre' => 'Neptuno', 'activo' => true]);

    Livewire::test(GestionClases::class)
        ->call('nuevo')
        ->set('nombre', 'tigers')
        ->set('sede_id', (string) $sede->id)
        ->set('grupo_etario', 'tigers')
        ->set('dia_semana', '1')
        ->set('hora_inicio', '18:30')
        ->set('hora_fin', '19:15')
        ->call('guardar')
        ->assertHasNoErrors();

    $clase = Clase::sinGrupo()->where('nombre', 'tigers')->first();

    expect($clase)->not->toBeNull()
        ->and($clase->grupo_id)->toBe($grupo->id);
});
