<?php

use App\Enums\TipoContenido;
use App\Models\EtapaPrograma;
use Database\Seeders\FederacionesSeeder;
use Database\Seeders\PreparacionJuezSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(FederacionesSeeder::class);
    $this->seed(PreparacionJuezSeeder::class);
});

test('la preparación para examen de juez se siembra como etapa de programa', function () {
    $etapa = EtapaPrograma::where('nombre', 'Preparación para examen de juez nivel 1')->first();

    expect($etapa)->not->toBeNull()
        ->and($etapa->programa->nombre)->toBe('Preparación para examen de juez')
        ->and($etapa->activo)->toBeTrue()
        // Intro + 18 secciones de manual + puntero a práctica + 2 documentos.
        ->and($etapa->contenidos()->count())->toBe(22);
});

test('incluye el manual, el puntero a la práctica y los documentos', function () {
    $etapa = EtapaPrograma::where('nombre', 'Preparación para examen de juez nivel 1')->first();
    $contenidos = $etapa->contenidos()->get();

    expect($contenidos->filter(fn ($c) => str_starts_with($c->titulo, 'Manual · ')))->toHaveCount(18);

    $practica = $contenidos->firstWhere('titulo', 'Práctica interactiva (autocorregida)');
    expect($practica)->not->toBeNull()
        ->and($practica->cuerpo)->toContain('módulo de Cuestionarios');

    $documentos = $contenidos->where('tipo', TipoContenido::Documento);
    expect($documentos)->toHaveCount(2)
        ->and($documentos->pluck('url_recurso')->every(fn ($u) => str_starts_with($u, 'https://drive.google.com/')))->toBeTrue();
});

test('el seeder es idempotente (no duplica al re-sembrar)', function () {
    $this->seed(PreparacionJuezSeeder::class);

    expect(EtapaPrograma::where('nombre', 'Preparación para examen de juez nivel 1')->count())->toBe(1);
});
