<?php

use App\Enums\TipoContenido;
use App\Models\Academia;
use App\Models\Nivel;
use App\Support\Tenancy\Academia as Tenant;
use Database\Seeders\PreparacionJuezSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    $this->seed(PreparacionJuezSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->bekho = Academia::where('nombre', 'BEKHO Power Academy')->first();
});

afterEach(fn () => Tenant::olvidar());

test('la preparación para examen de juez se siembra como nivel de Aprender', function () {
    Tenant::set($this->bekho->id);

    $nivel = Nivel::where('nombre', 'Preparación para examen de juez nivel 1')->first();

    expect($nivel)->not->toBeNull()
        ->and($nivel->academia_id)->toBe($this->bekho->id)
        ->and($nivel->activo)->toBeTrue()
        // Intro + 18 secciones de manual + 4 cuestionarios + 2 documentos.
        ->and($nivel->contenidos()->count())->toBe(25);
});

test('incluye el manual, los cuestionarios con respuestas y los documentos', function () {
    Tenant::set($this->bekho->id);

    $nivel = Nivel::where('nombre', 'Preparación para examen de juez nivel 1')->first();
    $contenidos = $nivel->contenidos()->get();

    // Manual de referencia: 18 secciones.
    expect($contenidos->filter(fn ($c) => str_starts_with($c->titulo, 'Manual · ')))->toHaveCount(18);

    // Cuestionario N1 con clave de respuestas y explicación.
    $n1 = $contenidos->firstWhere('titulo', 'Cuestionario · Nivel 1 (con respuestas)');
    expect($n1)->not->toBeNull()
        ->and($n1->tipo->value)->toBe('texto')
        ->and($n1->cuerpo)->toContain('Victoria Súbita')
        ->and($n1->cuerpo)->toContain('✔ Respuesta:');

    $documentos = $contenidos->where('tipo', TipoContenido::Documento);
    expect($documentos)->toHaveCount(2)
        ->and($documentos->pluck('url_recurso')->every(fn ($u) => str_starts_with($u, 'https://drive.google.com/')))->toBeTrue();
});

test('el seeder es idempotente (no duplica al re-sembrar)', function () {
    $this->seed(PreparacionJuezSeeder::class);

    Tenant::set($this->bekho->id);
    expect(Nivel::where('nombre', 'Preparación para examen de juez nivel 1')->count())->toBe(1);
});
