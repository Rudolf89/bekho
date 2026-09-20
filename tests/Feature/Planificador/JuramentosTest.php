<?php

use App\Enums\CategoriaJuramento;
use App\Enums\MomentoJuramento;
use App\Models\Juramento;
use Database\Seeders\JuramentosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(JuramentosSeeder::class));

test('se siembran los tres juramentos confirmados por la federación', function () {
    expect(Juramento::count())->toBe(3);

    $inicio = Juramento::where('nombre', 'Espíritu Songahm del Taekwondo')->first();
    expect($inicio->momento)->toBe(MomentoJuramento::Inicio)
        ->and($inicio->categoria_clase)->toBe(CategoriaJuramento::KidsAdultos)
        ->and($inicio->verificado)->toBeTrue()
        ->and($inicio->fuente)->toBe(JuramentosSeeder::FUENTE)
        ->and($inicio->texto)->toContain('Practicaré en el Espíritu del Taekwondo');

    $cierre = Juramento::where('nombre', 'Espíritu Songahm')->first();
    expect($cierre->momento)->toBe(MomentoJuramento::Cierre)
        ->and($cierre->categoria_clase)->toBe(CategoriaJuramento::KidsAdultos)
        ->and($cierre->texto)->toContain('Viviré con perseverancia');

    $tigers = Juramento::where('nombre', 'Juramento Tigers')->first();
    expect($tigers->momento)->toBe(MomentoJuramento::Ambos)
        ->and($tigers->categoria_clase)->toBe(CategoriaJuramento::Tigers)
        ->and($tigers->texto)->toContain('(Mano derecha levantada)')
        ->and($tigers->texto)->toContain('(Tomando el propio cinturón)');
});

test('el juramento Tigers sirve para el inicio y para el cierre', function () {
    $inicio = Juramento::paraCategoria(CategoriaJuramento::Tigers)->paraMomento(MomentoJuramento::Inicio)->pluck('nombre');
    $cierre = Juramento::paraCategoria(CategoriaJuramento::Tigers)->paraMomento(MomentoJuramento::Cierre)->pluck('nombre');

    expect($inicio)->toContain('Juramento Tigers')
        ->and($cierre)->toContain('Juramento Tigers');
});

test('el seeder es idempotente', function () {
    $this->seed(JuramentosSeeder::class);

    expect(Juramento::count())->toBe(3);
});
