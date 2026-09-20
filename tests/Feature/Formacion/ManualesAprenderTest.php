<?php

use App\Models\Contenido;
use App\Models\EtapaPrograma;
use Database\Seeders\FederacionesSeeder;
use Database\Seeders\ManualesAprenderSeeder;
use Database\Seeders\ProgramasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(FederacionesSeeder::class);
    $this->seed(ProgramasSeeder::class);
    $this->seed(ManualesAprenderSeeder::class);
});

test('cada manual es una etapa de su programa con sus contenidos', function () {
    $esperados = [
        'Programa ATA Legacy · Formación de facilitadores e instructores',
        'Programa ATA Tigers (preescolar y kínder)',
        'Programa MAK (Martial Arts Kids)',
        'Currículo MAX (Xtreme) · Nivel 1',
        'Currículo MAX (Xtreme) · Nivel 2',
    ];

    foreach ($esperados as $nombre) {
        $etapa = EtapaPrograma::where('nombre', $nombre)->first();
        expect($etapa)->not->toBeNull("Falta la etapa: {$nombre}")
            ->and($etapa->contenidos()->count())->toBeGreaterThan(3);
    }
});

test('el manual Legacy queda bajo el programa Legacy', function () {
    $etapa = EtapaPrograma::where('nombre', 'Programa ATA Legacy · Formación de facilitadores e instructores')->first();
    expect($etapa->programa->nombre)->toBe('Legacy');
});

test('las secciones se vuelcan como texto y el manual como documento', function () {
    $etapa = EtapaPrograma::where('nombre', 'Programa ATA Legacy · Formación de facilitadores e instructores')->first();

    $cuadrantes = $etapa->contenidos()->where('titulo', 'Cuadrantes de Enseñanza (Teaching Quadrants)')->first();
    expect($cuadrantes)->not->toBeNull()
        ->and($cuadrantes->tipo->value)->toBe('texto')
        ->and($cuadrantes->cuerpo)->toContain('Estructura', 'Emoción', 'Conocimiento', 'Legado');

    $doc = $etapa->contenidos()->where('titulo', 'Documento oficial')->first();
    expect($doc)->not->toBeNull()
        ->and($doc->tipo->value)->toBe('documento')
        ->and($doc->url_recurso)->toContain('drive.google.com');
});

test('el seeder es idempotente', function () {
    $antes = Contenido::count();
    $this->seed(ManualesAprenderSeeder::class);

    expect(Contenido::count())->toBe($antes);
});
