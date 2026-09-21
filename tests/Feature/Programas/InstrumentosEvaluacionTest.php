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
        ->toBe(['Fórmula y Armas', 'Sparring', 'Recuento de medallas'])
        // La rúbrica sale del xlsx oficial, así que va verificada.
        ->and($planillero->fuente)->toBe('Planilla de competencia oficial BEKHO (prueba de planillero)')
        ->and($planillero->verificado)->toBeTrue();
});

test('los valores de la rúbrica suman el puntaje máximo de 6,0 que declara la prueba', function () {
    $planillero = InstrumentoEvaluacion::where('nombre', 'Prueba de planillero')
        ->with('secciones.criterios')->first();

    $maximo = (float) $planillero->puntaje_maximo;
    $suma = round((float) $planillero->secciones->flatMap->criterios->sum('valor'), 2);

    // Si no cuadra, la transcripción está incompleta o el xlsx tiene un error:
    // se reporta la diferencia, NO se ajustan los valores para forzar el total.
    $this->assertSame($maximo, $suma, sprintf(
        'Los criterios de la prueba de planillero suman %.2f y el puntaje máximo es %.2f (diferencia %.2f).',
        $suma, $maximo, $suma - $maximo,
    ));

    // Por sección: 1,0 de información general + 1,5 de incidentes; 2,5 en
    // sparring; 1,0 el recuento de medallas.
    expect($planillero->secciones->mapWithKeys(fn ($seccion) => [
        $seccion->nombre => round((float) $seccion->criterios->sum('valor'), 2),
    ])->all())->toBe([
        'Fórmula y Armas' => 2.5,
        'Sparring' => 2.5,
        'Recuento de medallas' => 1.0,
    ]);
});

test('cada sección guarda el enunciado del caso tal como lo plantea el xlsx', function () {
    $secciones = InstrumentoEvaluacion::where('nombre', 'Prueba de planillero')
        ->first()->secciones()->get()->pluck('enunciado', 'nombre');

    expect($secciones['Fórmula y Armas'])->toContain('1. INFORMACIÓN GENERAL')
        ->toContain('11 participantes.')
        ->toContain('Pista nº 8.')
        ->toContain('Usted es el Planillero.')
        ->toContain('a.- (Competencia de Fórmula) El 5to participante se salta 4 pasos consecutivos en su fórmula.')
        ->and($secciones['Sparring'])
        ->toContain('d.- (Competencia de Sparring) en la primera ronda un participante recibe 2 advertencias de No contacto.')
        // El xlsx salta de "f.-" a "h.-": se transcribe literal, sin renumerar.
        ->toContain('h.-Usted genere los puntajes de las participantes por cada ronda y complete la planilla totalmente. 1 pto.')
        ->not->toContain('g.-')
        ->and($secciones['Recuento de medallas'])->toBe('REQUERIMIENTO DE MEDALLAS');
});

test('los ítems de la rúbrica quedan en el orden del xlsx, con su valor', function () {
    $criterios = InstrumentoEvaluacion::where('nombre', 'Prueba de planillero')
        ->first()->secciones()->where('nombre', 'Fórmula y Armas')->first()->criterios;

    expect($criterios)->toHaveCount(13)
        ->and($criterios->first()->nombre)->toBe('11 participantes.')
        ->and((float) $criterios->first()->valor)->toBe(0.1)
        ->and($criterios->last()->nombre)->toStartWith('c.- (Competencia de Armas)')
        ->and((float) $criterios->last()->valor)->toBe(0.5)
        ->and($criterios->pluck('orden')->all())->toBe(range(1, 13));
});

test('al resembrar, un criterio de relleno con puntajes se conserva y uno sin puntajes se retira', function () {
    $planillero = InstrumentoEvaluacion::where('nombre', 'Prueba de planillero')->first();
    $seccion = $planillero->secciones()->where('nombre', 'Sparring')->first();

    // Los criterios homónimos que se sembraban antes de tener el xlsx.
    $conHistorial = $seccion->criterios()->create(['nombre' => 'Sparring', 'orden' => 98]);
    $sinHistorial = $seccion->criterios()->create(['nombre' => 'Recuento de medallas', 'orden' => 99]);

    $persona = Persona::create(['nombres' => 'Planillera', 'fecha_nacimiento' => now()->subYears(25)]);
    EvaluacionPractica::create([
        'instrumento_evaluacion_id' => $planillero->id, 'persona_id' => $persona->id, 'fecha' => now(),
    ])->puntajes()->create(['criterio_instrumento_id' => $conHistorial->id, 'puntaje' => 2.0]);

    $this->seed(InstrumentosEvaluacionSeeder::class);

    // El historial manda: el criterio con puntajes NO se borra (se avisa).
    expect($conHistorial->fresh())->not->toBeNull()
        ->and($sinHistorial->fresh())->toBeNull();
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
