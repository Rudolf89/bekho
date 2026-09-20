<?php

use App\Enums\EscalaGrado;
use App\Enums\NivelEntrenamiento;
use App\Models\Grado;
use Database\Seeders\GradosSeeder;
use Database\Seeders\TramosEntrenamientoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(TramosEntrenamientoSeeder::class);
    $this->seed(GradosSeeder::class);
});

test('los grados ganan federacion_id y tramo_id', function () {
    expect(Schema::hasColumn('grados', 'federacion_id'))->toBeTrue()
        ->and(Schema::hasColumn('grados', 'tramo_id'))->toBeTrue();

    expect(Grado::whereNull('federacion_id')->count())->toBe(0)
        ->and(Grado::whereNull('tramo_id')->count())->toBe(0);
});

test('un grado de color enlaza su tramo y sugiere 2 meses sin nominación', function () {
    $amarillo = Grado::porEscala(EscalaGrado::Adultos)->where('color', 'Amarillo')->first();

    expect($amarillo->tramo->clave)->toBe(NivelEntrenamiento::Principiantes->value)
        ->and($amarillo->meses_sugeridos)->toBe(2)
        ->and($amarillo->requiere_nominacion)->toBeFalse();

    $intermedio = Grado::porEscala(EscalaGrado::Adultos)->where('color', 'Verde')->first();
    expect($intermedio->tramo->clave)->toBe(NivelEntrenamiento::Intermedio->value);
});

test('los danes exigen nominación y no llevan plazo sugerido', function () {
    $noveno = Grado::porEscala(EscalaGrado::Adultos)->where('nombre', '9º Dan')->first();

    expect($noveno->tramo->clave)->toBe(NivelEntrenamiento::Danes->value)
        ->and($noveno->requiere_nominacion)->toBeTrue()
        ->and($noveno->meses_sugeridos)->toBeNull();
});
