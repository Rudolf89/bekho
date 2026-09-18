<?php

use App\Models\Contenido;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Support\Tenancy\Grupo as Tenant;
use Database\Seeders\ManualesAprenderSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->bekho = Grupo::where('nombre', 'BEKHO Power Academy')->first();
    Tenant::set($this->bekho->id);
    $this->seed(ManualesAprenderSeeder::class);
});

test('cada manual crea un Nivel de Aprender con sus contenidos', function () {
    $esperados = [
        'Programa ATA Legacy · Formación de facilitadores e instructores',
        'Programa ATA Tigers (preescolar y kínder)',
        'Programa MAK (Martial Arts Kids)',
        'Currículo MAX (Xtreme) · Nivel 1',
        'Currículo MAX (Xtreme) · Nivel 2',
    ];

    foreach ($esperados as $nombre) {
        $nivel = Nivel::where('nombre', $nombre)->first();
        expect($nivel)->not->toBeNull("Falta el nivel: {$nombre}");
        // Intro + varias secciones + documento.
        expect($nivel->contenidos()->count())->toBeGreaterThan(3);
    }
});

test('las secciones se vuelcan como texto y el manual como documento', function () {
    $legacy = Nivel::where('nombre', 'Programa ATA Legacy · Formación de facilitadores e instructores')->first();

    // Una sección conocida del manual Legacy.
    $cuadrantes = $legacy->contenidos()->where('titulo', 'Cuadrantes de Enseñanza (Teaching Quadrants)')->first();
    expect($cuadrantes)->not->toBeNull()
        ->and($cuadrantes->tipo->value)->toBe('texto')
        ->and($cuadrantes->cuerpo)->toContain('Estructura', 'Emoción', 'Conocimiento', 'Legado');

    // El documento oficial en Drive.
    $doc = $legacy->contenidos()->where('titulo', 'Documento oficial')->first();
    expect($doc)->not->toBeNull()
        ->and($doc->tipo->value)->toBe('documento')
        ->and($doc->url_recurso)->toContain('drive.google.com');
});

test('el seeder es idempotente', function () {
    $antes = Contenido::count();
    $this->seed(ManualesAprenderSeeder::class);

    expect(Contenido::count())->toBe($antes);
});
