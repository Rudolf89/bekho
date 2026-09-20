<?php

use App\Models\CriterioInstrumento;
use App\Models\EvaluacionPractica;
use App\Models\InstrumentoEvaluacion;
use App\Models\Persona;
use Database\Seeders\CatalogosFederacionSeeder;
use Database\Seeders\FederacionesSeeder;
use Database\Seeders\InstrumentosEvaluacionSeeder;
use Database\Seeders\ManualLegacySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(FederacionesSeeder::class);
    $this->seed(CatalogosFederacionSeeder::class); // escalas Competencia y Rúbrica
    $this->seed(ManualLegacySeeder::class);        // atributos_tecnicos (10 + 3)
    $this->seed(InstrumentosEvaluacionSeeder::class);
});

test('la prueba de planillero usa la rúbrica 0–6.0 con umbral 80 % y tres secciones', function () {
    $planillero = InstrumentoEvaluacion::where('nombre', 'Prueba de planillero')->with('secciones', 'escala')->first();

    expect($planillero)->not->toBeNull()
        ->and($planillero->escala->nombre)->toBe('Rúbrica')
        ->and((float) $planillero->puntaje_maximo)->toBe(6.0)
        ->and($planillero->umbral_porcentaje)->toBe(80)
        ->and($planillero->nota_minima)->toBeNull()
        ->and($planillero->secciones->pluck('nombre')->all())
        ->toBe(['Fórmula y Armas', 'Sparring', 'Recuento de medallas']);
});

test('la evaluación de formas y patadas usa la escala 9.1–9.9 con 13 criterios atados a atributos', function () {
    $formas = InstrumentoEvaluacion::where('nombre', 'Evaluación de formas y patadas')->with('escala', 'secciones.criterios')->first();

    expect($formas->escala->nombre)->toBe('Competencia')
        ->and((float) $formas->nota_minima)->toBe(9.5);

    $criterios = $formas->secciones->flatMap->criterios;
    expect($criterios)->toHaveCount(13)
        // Los 13 corresponden a un atributo técnico del catálogo (10 + 3).
        ->and($criterios->whereNull('atributo_tecnico_id')->count())->toBe(0);
});

test('se puede registrar una evaluación práctica de una persona con puntajes por criterio', function () {
    $persona = Persona::create(['nombres' => 'Evaluado', 'fecha_nacimiento' => now()->subYears(20)]);
    $planillero = InstrumentoEvaluacion::where('nombre', 'Prueba de planillero')->first();

    $evaluacion = EvaluacionPractica::create([
        'instrumento_evaluacion_id' => $planillero->id, 'persona_id' => $persona->id,
        'fecha' => now(), 'puntaje_obtenido' => 5.4, 'porcentaje' => 90, 'aprobado' => true,
    ]);

    $criterio = CriterioInstrumento::whereHas('seccion', fn ($q) => $q->where('instrumento_evaluacion_id', $planillero->id))->first();
    $evaluacion->puntajes()->create(['criterio_instrumento_id' => $criterio->id, 'puntaje' => 5.5]);

    expect($evaluacion->fresh()->aprobado)->toBeTrue()
        ->and($evaluacion->puntajes()->count())->toBe(1);
});

test('el seeder de instrumentos es idempotente', function () {
    $antes = InstrumentoEvaluacion::count();
    $criterios = CriterioInstrumento::count();

    $this->seed(InstrumentosEvaluacionSeeder::class);

    expect(InstrumentoEvaluacion::count())->toBe($antes)
        ->and(CriterioInstrumento::count())->toBe($criterios);
});
