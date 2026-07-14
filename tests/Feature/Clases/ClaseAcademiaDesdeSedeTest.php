<?php

use App\Livewire\Clases\GestionClases;
use App\Models\Academia;
use App\Models\Clase;
use App\Models\Sede;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    foreach (['super-admin', 'maestro', 'instructor'] as $rol) {
        Role::findOrCreate($rol, 'web');
    }
});

test('el super-admin (sin academia) crea una clase y la academia se toma de la sede', function () {
    $admin = User::factory()->create(['academia_id' => null]);
    $admin->assignRole('super-admin');
    actingAs($admin);

    $academia = Academia::create(['nombre' => 'ATA', 'activo' => true]);
    $sede = Sede::create(['academia_id' => $academia->id, 'nombre' => 'Neptuno', 'activo' => true]);

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

    $clase = Clase::sinAcademia()->where('nombre', 'tigers')->first();

    expect($clase)->not->toBeNull()
        ->and($clase->academia_id)->toBe($academia->id);
});
