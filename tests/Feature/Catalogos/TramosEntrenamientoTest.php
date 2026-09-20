<?php

use App\Enums\NivelEntrenamiento;
use App\Models\TramoEntrenamiento;
use App\Support\Tenancy\Grupo as Tenant;
use Database\Seeders\TramosEntrenamientoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('los tramos son un catálogo de la federación (con federacion_id, sin grupo_id)', function () {
    expect(Schema::hasColumn('tramos_entrenamiento', 'federacion_id'))->toBeTrue()
        ->and(Schema::hasColumn('tramos_entrenamiento', 'grupo_id'))->toBeFalse();
});

test('el seeder crea los cinco tramos de BEKHO y es idempotente', function () {
    $this->seed(TramosEntrenamientoSeeder::class);
    $this->seed(TramosEntrenamientoSeeder::class); // no duplica

    expect(TramoEntrenamiento::count())->toBe(5)
        ->and(TramoEntrenamiento::ordenados()->pluck('clave')->all())->toBe(
            array_map(fn ($n) => $n->value, NivelEntrenamiento::cases())
        );
});

test('el catálogo de tramos no se filtra por grupo activo (es transversal)', function () {
    $this->seed(TramosEntrenamientoSeeder::class);

    Tenant::set(1);
    expect(TramoEntrenamiento::count())->toBe(5);
    Tenant::olvidar();
});

test('cada tramo coincide en nombre y color con el enum NivelEntrenamiento', function () {
    $this->seed(TramosEntrenamientoSeeder::class);

    $principiantes = TramoEntrenamiento::where('clave', 'principiantes')->first();

    expect($principiantes->nombre)->toBe(NivelEntrenamiento::Principiantes->etiqueta())
        ->and($principiantes->color)->toBe(NivelEntrenamiento::Principiantes->color())
        ->and($principiantes->orden)->toBe(1);
});
