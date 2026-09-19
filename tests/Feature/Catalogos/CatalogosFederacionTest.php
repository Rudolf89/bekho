<?php

use App\Models\EscalaPuntaje;
use App\Models\TipoCargo;
use Database\Seeders\CatalogosFederacionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('escalas_puntaje y tipos_cargo son catálogos de la federación', function () {
    expect(Schema::hasColumn('escalas_puntaje', 'federacion_id'))->toBeTrue()
        ->and(Schema::hasColumn('escalas_puntaje', 'grupo_id'))->toBeFalse()
        ->and(Schema::hasColumn('tipos_cargo', 'federacion_id'))->toBeTrue()
        ->and(Schema::hasColumn('tipos_cargo', 'grupo_id'))->toBeFalse();
});

test('el seeder crea las escalas de puntaje y es idempotente', function () {
    $this->seed(CatalogosFederacionSeeder::class);
    $this->seed(CatalogosFederacionSeeder::class);

    expect(EscalaPuntaje::count())->toBe(2);

    $competencia = EscalaPuntaje::where('nombre', 'Competencia')->first();
    expect((float) $competencia->minimo)->toBe(9.1)
        ->and((float) $competencia->maximo)->toBe(9.9)
        ->and((float) $competencia->paso)->toBe(0.1);
});

test('el seeder crea los tipos de cargo con sus banderas', function () {
    $this->seed(CatalogosFederacionSeeder::class);
    $this->seed(CatalogosFederacionSeeder::class); // no duplica

    expect(TipoCargo::count())->toBe(7);

    $mensualidad = TipoCargo::where('nombre', 'Mensualidad')->first();
    expect($mensualidad->recurrente)->toBeTrue()
        ->and($mensualidad->requiere_periodo)->toBeTrue();

    $matricula = TipoCargo::where('nombre', 'Matrícula')->first();
    expect($matricula->recurrente)->toBeFalse();
});
