<?php

use App\Livewire\Planillas\ClassPlanners;
use App\Models\Academia;
use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->bekho = Academia::where('nombre', 'BEKHO Power Academy')->first();

    $this->instructor = User::factory()->create(['academia_id' => $this->bekho->id]);
    $this->instructor->assignRole('instructor');
});

test('el planificador de principiantes muestra su fórmula y patadas', function () {
    Livewire::actingAs($this->instructor)->test(ClassPlanners::class)
        ->assertSet('nivel', 'principiantes')
        ->assertSee('Beginners')
        ->assertSee('Songahm 3')
        ->assertSee('Estructura')
        ->assertSee('Legado');
});

test('al cambiar de nivel se muestra el planificador correspondiente', function () {
    Livewire::actingAs($this->instructor)->test(ClassPlanners::class)
        ->set('nivel', 'avanzado')
        ->assertSee('Advanced')
        ->assertSee('Choong Jung 1')
        ->set('nivel', 'intermedio')
        ->assertSee('Intermediate')
        ->assertSee('In-Wha 1');
});

test('la vista incluye las diferencias de programas', function () {
    Livewire::actingAs($this->instructor)->test(ClassPlanners::class)
        ->assertSee('Alumno Taekwondo')
        ->assertSee('Alumno TKD Leadership');
});

test('la página de class planner exige el permiso de gestionar planillas', function () {
    $apoderado = User::factory()->create(['academia_id' => $this->bekho->id]);
    $apoderado->assignRole('apoderado');

    $this->actingAs($this->instructor)->get(route('class-planners.index'))->assertOk();
    $this->actingAs($apoderado)->get(route('class-planners.index'))->assertForbidden();
});
