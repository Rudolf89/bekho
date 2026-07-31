<?php

use App\Models\Cuestionario;
use App\Models\RequisitoLegacy;
use Database\Seeders\CuestionariosSeeder;
use Database\Seeders\LegacySeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    // El orden importa: el banco se siembra antes de enlazarlo al requisito.
    $this->seed(CuestionariosSeeder::class);
    $this->seed(LegacySeeder::class);
});

test('se siembra la prueba escrita de Legacy N3 con sus preguntas', function () {
    $cuestionario = Cuestionario::where('titulo', 'Examen escrito · Programa Legacy Nivel 3')->first();

    expect($cuestionario)->not->toBeNull()
        ->and($cuestionario->umbral_aprobacion)->toBe(80)
        ->and($cuestionario->preguntas()->count())->toBeGreaterThanOrEqual(15);
});

test('cada pregunta del banco Legacy tiene exactamente una opción correcta', function () {
    $cuestionario = Cuestionario::where('titulo', 'Examen escrito · Programa Legacy Nivel 3')->first();

    foreach ($cuestionario->preguntas as $pregunta) {
        expect($pregunta->opciones()->where('correcta', true)->count())->toBe(1);
    }
});

test('el requisito de prueba escrita N3 queda enlazado al cuestionario', function () {
    $requisito = RequisitoLegacy::where('texto', 'Prueba escrita de Nivel 3 aprobada')->first();

    expect($requisito)->not->toBeNull()
        ->and($requisito->cuestionario_id)->not->toBeNull()
        ->and($requisito->esAutomatico())->toBeTrue()
        ->and($requisito->cuestionario->titulo)->toBe('Examen escrito · Programa Legacy Nivel 3');
});

test('los demás requisitos siguen siendo checklist manual', function () {
    $requisito = RequisitoLegacy::where('texto', '100 horas de asistencia acreditadas')->first();

    expect($requisito->cuestionario_id)->toBeNull()
        ->and($requisito->esAutomatico())->toBeFalse();
});
