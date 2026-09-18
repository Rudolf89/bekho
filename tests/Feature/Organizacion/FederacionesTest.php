<?php

use App\Models\Federacion;
use Database\Seeders\FederacionesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('la federación BEKHO se siembra y es idempotente', function () {
    (new FederacionesSeeder)->run();
    (new FederacionesSeeder)->run(); // segunda vez: no duplica

    expect(Federacion::count())->toBe(1);

    $bekho = Federacion::first();
    expect($bekho->nombre)->toBe('BEKHO')
        ->and($bekho->pais)->toBe('Chile')
        ->and($bekho->moneda)->toBe('CLP')
        ->and($bekho->activo)->toBeTrue();
});
