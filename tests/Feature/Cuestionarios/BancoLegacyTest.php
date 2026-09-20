<?php

use App\Models\Cuestionario;
use App\Models\Programa;
use App\Models\RequisitoEtapa;
use Database\Seeders\CuestionariosSeeder;
use Database\Seeders\EtapasProgramaSeeder;
use Database\Seeders\FederacionesSeeder;
use Database\Seeders\GradosSeeder;
use Database\Seeders\ProgramasSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    $this->seed(FederacionesSeeder::class);
    $this->seed(ProgramasSeeder::class);
    $this->seed(GradosSeeder::class);
    // El orden importa: el banco se siembra antes de enlazarlo al requisito.
    $this->seed(CuestionariosSeeder::class);
    $this->seed(EtapasProgramaSeeder::class);
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
    $legacy = Programa::where('nombre', 'Legacy')->first();
    $n3 = $legacy->etapas()->where('orden', 3)->first();

    $requisito = $n3->requisitos()->where('tipo', 'cuestionario')->first();

    expect($requisito)->not->toBeNull()
        ->and($requisito->cuestionario_id)->not->toBeNull()
        ->and($requisito->esAutomatico())->toBeTrue()
        ->and($requisito->cuestionario->titulo)->toBe('Examen escrito · Programa Legacy Nivel 3');
});

test('los demás requisitos siguen siendo checklist manual', function () {
    $manual = RequisitoEtapa::where('tipo', 'manual')->first();

    expect($manual)->not->toBeNull()
        ->and($manual->cuestionario_id)->toBeNull()
        ->and($manual->esAutomatico())->toBeFalse();
});
