<?php

use App\Enums\CategoriaTecnica;
use App\Livewire\Planificador\BibliotecaTecnicas;
use App\Models\Grupo;
use App\Models\Tecnica;
use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Database\Seeders\TecnicasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    $this->seed(TecnicasSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->bekho = Grupo::where('nombre', 'BEKHO Power Academy')->first();
});

test('la biblioteca de técnicas se siembra con todas las categorías', function () {
    expect(Tecnica::deCategoria(CategoriaTecnica::Patada)->count())->toBeGreaterThan(10)
        ->and(Tecnica::deCategoria(CategoriaTecnica::Forma)->count())->toBe(12)
        ->and(Tecnica::deCategoria(CategoriaTecnica::Arma)->count())->toBe(6)
        ->and(Tecnica::deCategoria(CategoriaTecnica::Mano)->count())->toBe(9)
        ->and(Tecnica::deCategoria(CategoriaTecnica::Trick)->count())->toBe(12);
});

test('las técnicas con secuencia guardan sus pasos por segmento', function () {
    $jahngBong = Tecnica::where('nombre', 'Jahng Bong — Release')->with('pasos')->first();

    expect($jahngBong)->not->toBeNull()
        ->and($jahngBong->pasos->count())->toBeGreaterThan(10)
        // Tiene segmentos nombrados.
        ->and($jahngBong->pasos->pluck('segmento')->unique()->all())->toContain('Segmento 1', 'Segmento 2', 'Segmento 3');
});

test('las técnicas son transversales (contenido compartido, sin grupo)', function () {
    // La tabla no tiene grupo_id: se ven igual con o sin tenant.
    expect(Tecnica::count())->toBeGreaterThan(50);
});

test('la biblioteca filtra por categoría y modalidad', function () {
    $instructor = User::factory()->create(['grupo_id' => $this->bekho->id]);
    $instructor->assignRole('instructor');

    Livewire::actingAs($instructor)->test(BibliotecaTecnicas::class)
        ->set('categoria', 'trick')
        ->assertSee('Cartwheel')
        ->assertSee('Wushu Butterfly Kick')
        ->assertDontSee('Jahng Bong — Release'); // es un arma, no un trick
});

test('la biblioteca exige el permiso de gestionar planificaciones', function () {
    $instructor = User::factory()->create(['grupo_id' => $this->bekho->id]);
    $instructor->assignRole('instructor');
    $apoderado = User::factory()->create(['grupo_id' => $this->bekho->id]);
    $apoderado->assignRole('apoderado');

    $this->actingAs($instructor)->get(route('biblioteca.index'))->assertOk();
    $this->actingAs($apoderado)->get(route('biblioteca.index'))->assertForbidden();
});
