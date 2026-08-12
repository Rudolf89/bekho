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

test('BEKHO cubre 9→5 por grado exacto y 4→1 por banda; ATA cubre 9→1', function () {
    // Verde (grado 5): ya viene con su detalle exacto dictado por la escuela.
    $verde = Grado::porEscala(EscalaGrado::Adultos)->where('color', 'Verde')->first();
    $patadasVerde = $verde->tecnicas->filter(fn ($t) => $t->categoria === CategoriaTecnica::Patada);

    expect($patadasVerde->where('fuente', 'bekho')->pluck('nombre'))
        ->toContain('Giro circular', 'Patada de Costado saltando')
        ->and($patadasVerde->where('fuente', 'ata')->pluck('nombre'))->toContain('Patada lateral en salto');

    // Rojo (banda Avanzado): patadas saltando del planner + referencia ATA.
    $rojo = Grado::porEscala(EscalaGrado::Adultos)->where('color', 'Rojo')->first();
    expect($rojo->tecnicas->where('fuente', 'bekho')->pluck('nombre'))
        ->toContain('Patada circular saltando (afuera/adentro)', 'Giro circular saltando')
        ->and($rojo->tecnicas->where('fuente', 'ata')->pluck('nombre'))
        ->toContain('Patada de gancho en salto', 'Patada circular en salto');
});

test('las patadas de banda se enlazan a todos los colores de la banda', function () {
    // Banda Intermedio → solo Púrpura (grado 4, aún sin dictar).
    $purpura = Grado::porEscala(EscalaGrado::Adultos)->where('color', 'Púrpura')->first();
    expect($purpura->tecnicas->where('fuente', 'bekho')->pluck('nombre'))
        ->toContain('Patada de Gancho', 'Giro de Gancho');

    // Banda Avanzado (Azul/Café/Rojo): saltos + gancho saltando + mariposa.
    foreach (['Azul', 'Café', 'Rojo'] as $color) {
        $grado = Grado::porEscala(EscalaGrado::Adultos)->where('color', $color)->first();
        expect($grado->tecnicas->where('fuente', 'bekho')->pluck('nombre'))
            ->toContain('Giro circular saltando', 'Patada de Gancho saltando', 'Giro Mariposa');
    }

    // Verde ya es exacto: NO recibe las patadas de banda (Gancho) que quedan en Púrpura.
    $verde = Grado::porEscala(EscalaGrado::Adultos)->where('color', 'Verde')->first();
    expect($verde->tecnicas->where('fuente', 'bekho')->pluck('nombre'))
        ->toContain('Giro circular')
        ->not->toContain('Patada de Gancho');
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
