<?php

use App\Livewire\Planificador\Formas;
use App\Models\Grupo;
use App\Models\User;
use Database\Seeders\FormasPasosSeeder;
use Database\Seeders\ManualLegacySeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    $this->seed(ManualLegacySeeder::class);
    $this->seed(FormasPasosSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->bekho = Grupo::where('nombre', 'BEKHO Power Academy')->first();
});

test('la página de formas exige el permiso de gestionar planificaciones', function () {
    $alumno = User::factory()->create(['grupo_id' => $this->bekho->id]);
    $alumno->assignRole('alumno');

    $this->actingAs($alumno)->get(route('formas.index'))->assertForbidden();
});

test('la página de formas renderiza y lista las formas', function () {
    $instructor = User::factory()->create(['grupo_id' => $this->bekho->id]);
    $instructor->assignRole('instructor');

    Livewire::actingAs($instructor)->test(Formas::class)
        ->assertOk()
        ->assertSee('Songahm Il-Jahng n.º 1')
        ->assertSee('Sok Bong'); // la nombrada sin secuencia también aparece
});

test('el buscador filtra las formas', function () {
    $instructor = User::factory()->create(['grupo_id' => $this->bekho->id]);
    $instructor->assignRole('instructor');

    Livewire::actingAs($instructor)->test(Formas::class)
        ->set('buscar', 'Chung San')
        ->assertSee('Chung San')
        ->assertDontSee('Songahm Il-Jahng n.º 1');
});
