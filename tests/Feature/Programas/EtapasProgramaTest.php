<?php

use App\Enums\TipoRequisitoEtapa;
use App\Models\Programa;
use App\Models\RequisitoEtapa;
use Database\Seeders\EtapasProgramaSeeder;
use Database\Seeders\FederacionesSeeder;
use Database\Seeders\GradosSeeder;
use Database\Seeders\ProgramasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(FederacionesSeeder::class);
    $this->seed(ProgramasSeeder::class);
    $this->seed(GradosSeeder::class);
    $this->seed(EtapasProgramaSeeder::class);
});

test('todos los programas quedan anclados a la federación', function () {
    expect(Programa::whereNull('federacion_id')->count())->toBe(0);
});

test('las etapas del programa Legacy se siembran desde el JSON', function () {
    $legacy = Programa::where('nombre', 'Legacy')->first();
    $operativas = $legacy->etapas()->whereNotNull('horas_requeridas')->orderBy('orden')->get();

    expect($operativas)->toHaveCount(3)
        ->and($operativas->pluck('horas_requeridas')->all())->toBe([100, 100, 100])
        ->and($operativas->pluck('edad_minima')->all())->toBe([13, 16, 18]);

    // El grado mínimo (1.er Dan) solo lo exige la etapa 3.
    expect($operativas[0]->grado_minimo_id)->toBeNull()
        ->and($operativas[2]->grado_minimo_id)->not->toBeNull();

    $escrito = $operativas[2]->requisitos()->where('tipo', TipoRequisitoEtapa::Cuestionario->value)->first();
    expect($escrito)->not->toBeNull();
});

test('los requisitos Protech se siembran (14: 4/6/4) verificados y con fuente', function () {
    $legacy = Programa::where('nombre', 'Legacy')->first();
    $etapas = $legacy->etapas()->whereNotNull('horas_requeridas')->orderBy('orden')->get();

    $protech = fn ($etapa) => $etapa->requisitos()->where('descripcion', 'like', 'Protech%');

    expect($protech($etapas[0])->count())->toBe(4)
        ->and($protech($etapas[1])->count())->toBe(6)
        ->and($protech($etapas[2])->count())->toBe(4);

    $todos = RequisitoEtapa::where('descripcion', 'like', 'Protech%')->get();
    expect($todos)->toHaveCount(14)
        ->and($todos->every(fn ($r) => $r->verificado === true))->toBeTrue()
        ->and($todos->every(fn ($r) => str_contains((string) $r->fuente, 'Manual')))->toBeTrue();

    // El Nivel 1 trae la condición general de líneas de golpeo.
    expect($protech($etapas[0])->where('descripcion', 'like', 'Protech (condición general)%')->exists())->toBeTrue();
});

test('los Protech se suman a los requisitos de legacy_niveles.json (no los reemplazan)', function () {
    $legacy = Programa::where('nombre', 'Legacy')->first();
    $n1 = $legacy->etapas()->where('orden', 1)->first();

    // El Nivel 1 conserva sus requisitos del reglamento (membresía, etc.) además de Protech.
    expect($n1->requisitos()->where('descripcion', 'not like', 'Protech%')->count())->toBeGreaterThan(0)
        ->and($n1->requisitos()->where('descripcion', 'like', 'Protech%')->count())->toBe(4);
});

test('el seeder es idempotente', function () {
    (new EtapasProgramaSeeder)->run();

    $legacy = Programa::where('nombre', 'Legacy')->first();
    expect($legacy->etapas()->whereNotNull('horas_requeridas')->count())->toBe(3)
        ->and(RequisitoEtapa::where('descripcion', 'like', 'Protech%')->count())->toBe(14);
});
