<?php

use App\Models\Tecnica;
use Database\Seeders\FormasPasosSeeder;
use Database\Seeders\TecnicasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(TecnicasSeeder::class);
    $this->seed(FormasPasosSeeder::class);
});

test('todas las formas Songahm reciben su paso a paso', function () {
    $formas = Tecnica::where('categoria', 'forma')->get();

    expect($formas)->toHaveCount(12);
    $formas->each(fn ($f) => expect($f->pasos()->count())->toBeGreaterThan(0));

    // Conteos representativos transcritos del Manual Legacy. Chung San son 83
    // pasos: los 36 «extra» de la transcripción original eran las tablas de los
    // Cuadrantes de Enseñanza, arrastradas por error (se corrigieron).
    expect(Tecnica::where('nombre', 'Songahm Il-Jahng n.º 1')->first()->pasos()->count())->toBe(18)
        ->and(Tecnica::where('nombre', 'Chung San')->first()->pasos()->count())->toBe(83);
});

test('los pasos guardan técnica, lado, postura y sección por columna', function () {
    $forma = Tecnica::where('nombre', 'Songahm Il-Jahng n.º 1')->with('pasos')->first();
    $primero = $forma->pasos->firstWhere('orden', 1);

    expect($primero->texto)->toBe('Bloqueo alto')
        ->and($primero->lado)->toBe('Izq.')
        ->and($primero->postura)->toBe('Frontal')
        ->and($primero->seccion)->toBe('Alta')
        ->and($forma->descripcion)->toContain('18 movimientos');
});

test('el seeder es idempotente (no duplica pasos)', function () {
    $this->seed(FormasPasosSeeder::class);

    expect(Tecnica::where('nombre', 'Songahm Il-Jahng n.º 1')->first()->pasos()->count())->toBe(18);
});
