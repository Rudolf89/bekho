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

test('cada cinturón tiene sus patadas en las dos fuentes (bekho y ata)', function () {
    $blanco = Grado::porEscala(EscalaGrado::Adultos)->where('color', 'Blanco')->first();
    $patadas = $blanco->tecnicas->filter(fn ($t) => $t->categoria === CategoriaTecnica::Patada);

    // BEKHO (examen): la nomenclatura del usuario.
    expect($patadas->where('fuente', 'bekho')->pluck('nombre'))
        ->toContain('Patada de Frente', 'Patada de Costado', 'Levantamiento de pierna recto');

    // ATA (manual): la referencia oficial.
    expect($patadas->where('fuente', 'ata')->pluck('nombre'))->toContain('Patada lateral');
});

test('BEKHO cubre 9→6; ATA cubre 9→1 (referencia completa)', function () {
    // BEKHO aún no tiene 5→1 (Verde-Rojo).
    $verde = Grado::porEscala(EscalaGrado::Adultos)->where('color', 'Verde')->first();
    $patadasVerde = $verde->tecnicas->filter(fn ($t) => $t->categoria === CategoriaTecnica::Patada);

    expect($patadasVerde->where('fuente', 'bekho'))->toHaveCount(0)
        ->and($patadasVerde->where('fuente', 'ata')->pluck('nombre'))->toContain('Patada lateral en salto');

    // ATA de un cinturón alto (Rojo).
    $rojo = Grado::porEscala(EscalaGrado::Adultos)->where('color', 'Rojo')->first();
    expect($rojo->tecnicas->where('fuente', 'ata')->pluck('nombre'))
        ->toContain('Patada de gancho en salto', 'Patada circular en salto');
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
