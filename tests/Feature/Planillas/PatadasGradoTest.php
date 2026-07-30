<?php

use App\Enums\CategoriaTecnica;
use App\Enums\EscalaGrado;
use App\Models\Grado;
use App\Models\Tecnica;
use Database\Seeders\GradosSeeder;
use Database\Seeders\GradoTecnicaSeeder;
use Database\Seeders\PatadasGradoSeeder;
use Database\Seeders\TecnicasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(GradosSeeder::class);
    $this->seed(TecnicasSeeder::class);
    $this->seed(GradoTecnicaSeeder::class);
    $this->seed(PatadasGradoSeeder::class);
});

test('las patadas detalladas quedan enlazadas a su cinturón', function () {
    $blanco = Grado::porEscala(EscalaGrado::Adultos)->where('color', 'Blanco')->first();
    $nombres = $blanco->tecnicas->pluck('nombre');

    expect($nombres)->toContain('Patada de Frente', 'Patada de Costado', 'Levantamiento de pierna recto');

    // La patada aplica a los grados Blanco de todas las escalas.
    $frente = Tecnica::where('nombre', 'Patada de Frente')->first();
    expect($frente->grados()->count())->toBeGreaterThan(1)
        ->and($frente->descripcion)->toContain('N1, N2, N3, N4');
});

test('los cinturones de color 9→1 tienen sus patadas detalladas (manual)', function () {
    $verde = Grado::porEscala(EscalaGrado::Adultos)->where('color', 'Verde')->first();
    $rojo = Grado::porEscala(EscalaGrado::Adultos)->where('color', 'Rojo')->first();

    expect($verde->tecnicas->pluck('nombre'))->toContain('Patada lateral', 'Patada lateral en salto')
        ->and($rojo->tecnicas->pluck('nombre'))->toContain('Patada de gancho en salto', 'Patada circular en salto');
});

test('las técnicas-resumen de los cinturones de color se reemplazan', function () {
    foreach (['Blanco', 'Camuflaje', 'Verde', 'Morado', 'Azul', 'Marrón', 'Rojo'] as $c) {
        expect(Tecnica::where('nombre', "Patadas de cinturón {$c}")->exists())->toBeFalse();
    }
});

test('el seeder es idempotente', function () {
    $this->seed(PatadasGradoSeeder::class);

    expect(Tecnica::where('categoria', CategoriaTecnica::Patada->value)->where('nombre', 'Patada de Frente')->count())->toBe(1);
});
