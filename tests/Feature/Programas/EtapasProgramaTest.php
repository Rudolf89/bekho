<?php

use App\Enums\TipoRequisitoEtapa;
use App\Models\Contenido;
use App\Models\EtapaPrograma;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\Programa;
use App\Support\Tenancy\Grupo as Tenant;
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

    // Un nivel del LMS (manual Tigers) con un contenido, para probar la fusión.
    Tenant::comoSistema(function () {
        $grupo = Grupo::create(['nombre' => 'Academia Test', 'activo' => true]);
        $nivel = Nivel::create(['grupo_id' => $grupo->id, 'nombre' => 'Programa ATA Tigers (test)', 'orden' => 1, 'activo' => true]);
        Contenido::create([
            'grupo_id' => $grupo->id, 'nivel_id' => $nivel->id,
            'titulo' => 'Sección 1', 'tipo' => 'texto', 'cuerpo' => 'x', 'orden' => 1, 'activo' => true,
        ]);
    });
});

afterEach(fn () => Tenant::olvidar());

test('todos los programas quedan anclados a la federación', function () {
    (new EtapasProgramaSeeder)->run();

    expect(Programa::whereNull('federacion_id')->count())->toBe(0);
});

test('las etapas del programa Legacy se siembran desde el JSON', function () {
    (new EtapasProgramaSeeder)->run();

    $legacy = Programa::where('nombre', 'Legacy')->first();
    $operativas = $legacy->etapas()->whereNotNull('horas_requeridas')->orderBy('orden')->get();

    expect($operativas)->toHaveCount(3)
        ->and($operativas->pluck('horas_requeridas')->all())->toBe([100, 100, 100])
        ->and($operativas->pluck('edad_minima')->all())->toBe([13, 16, 18]);

    // El grado mínimo (1.er Dan) solo lo exige la etapa 3.
    expect($operativas[0]->grado_minimo_id)->toBeNull()
        ->and($operativas[2]->grado_minimo_id)->not->toBeNull();

    // La prueba escrita del Nivel 3 es un requisito de tipo cuestionario.
    $escrito = $operativas[2]->requisitos()->where('tipo', TipoRequisitoEtapa::Cuestionario->value)->first();
    expect($escrito)->not->toBeNull();
});

test('los niveles del LMS se fusionan en etapas y arrastran sus contenidos', function () {
    (new EtapasProgramaSeeder)->run();

    // El manual Tigers pasó a ser una etapa del programa Tigers.
    $tigers = Programa::where('nombre', 'Tigers')->first();
    expect($tigers)->not->toBeNull();

    $etapa = EtapaPrograma::where('programa_id', $tigers->id)->where('nombre', 'Programa ATA Tigers (test)')->first();
    expect($etapa)->not->toBeNull();

    // El contenido quedó enlazado a la etapa (sin perder su nivel_id).
    $contenido = Contenido::withoutGlobalScopes()->where('titulo', 'Sección 1')->first();
    expect($contenido->etapa_programa_id)->toBe($etapa->id)
        ->and($contenido->nivel_id)->not->toBeNull();
});

test('el seeder es idempotente', function () {
    (new EtapasProgramaSeeder)->run();
    $etapas = EtapaPrograma::count();
    $programas = Programa::count();

    (new EtapasProgramaSeeder)->run();

    expect(EtapaPrograma::count())->toBe($etapas)
        ->and(Programa::count())->toBe($programas);
});
