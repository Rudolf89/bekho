<?php

use App\Models\Forma;
use Database\Seeders\FormasPasosSeeder;
use Database\Seeders\ManualLegacySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // ManualLegacySeeder siembra el catálogo de posiciones que usan las formas.
    $this->seed(ManualLegacySeeder::class);
    $this->seed(FormasPasosSeeder::class);
});

test('las 12 formas detalladas reciben su paso a paso y quedan verificadas', function () {
    $conPasos = Forma::where('verificado', true)->get();

    expect($conPasos)->toHaveCount(12);
    $conPasos->each(fn (Forma $f) => expect($f->pasos()->count())->toBeGreaterThan(0));

    expect(Forma::where('nombre', 'Songahm Il-Jahng n.º 1')->first()->pasos()->count())->toBe(18)
        ->and(Forma::where('nombre', 'Chung San')->first()->pasos()->count())->toBe(83);
});

test('las 5 formas de cinturón negro sin secuencia se crean sin pasos y sin verificar', function () {
    $sinDetalle = ['Sok Bong', 'Chung Hae', 'Jahng Soo', 'Chul Joon', 'Jeong Seung'];

    foreach ($sinDetalle as $nombre) {
        $forma = Forma::where('nombre', $nombre)->first();
        expect($forma)->not->toBeNull()
            ->and($forma->verificado)->toBeFalse()
            ->and($forma->pasos()->count())->toBe(0);
    }

    // 12 detalladas + 5 sin detalle.
    expect(Forma::count())->toBe(17);
});

test('los pasos guardan técnica, lado, posición (catálogo) y sección', function () {
    $forma = Forma::where('nombre', 'Songahm Il-Jahng n.º 1')->with('pasos.posicion')->first();
    $primero = $forma->pasos->firstWhere('numero', 1);

    expect($primero->tecnica)->toBe('Bloqueo alto')
        ->and($primero->lado)->toBe('Izq.')
        ->and($primero->posicion?->nombre)->toBe('Frontal')
        ->and($primero->seccion)->toBe('Alta');
});

test('una patada (sin postura) resuelve la posición «--» del catálogo', function () {
    $forma = Forma::where('nombre', 'Songahm Il-Jahng n.º 1')->with('pasos.posicion')->first();
    // El 3.er movimiento es «Patada frontal n.º 2» (sin postura).
    $patada = $forma->pasos->firstWhere('numero', 3);

    expect($patada->tecnica)->toContain('Patada frontal')
        ->and($patada->posicion?->codigo)->toBe('--');
});

test('el seeder es idempotente (no duplica pasos)', function () {
    $this->seed(FormasPasosSeeder::class);

    expect(Forma::where('nombre', 'Songahm Il-Jahng n.º 1')->first()->pasos()->count())->toBe(18)
        ->and(Forma::count())->toBe(17);
});
