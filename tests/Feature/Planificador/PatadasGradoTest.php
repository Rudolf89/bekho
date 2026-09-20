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

test('BEKHO cubre los 9 grados de color por grado exacto (sin bandas)', function () {
    // Cada color tiene EXACTAMENTE sus patadas dictadas por la escuela.
    $esperado = [
        'Verde' => ['Giro circular', 'Patada de Costado saltando'],
        'Púrpura' => ['Patada de Gancho', 'Giro de Gancho', 'Patada de Vuelta saltando'],
        'Azul' => ['Patada Circular saltando (adentro/afuera)', 'Giro circular saltando'],
        'Café' => ['Patada Circular de talón', 'Giro de talón', 'Giro de costado saltando'],
        'Rojo' => ['Patada de Gancho saltando', 'Giro de Gancho saltando'],
    ];

    foreach ($esperado as $color => $patadas) {
        $grado = Grado::porEscala(EscalaGrado::Adultos)->where('color', $color)->first();
        $bekho = $grado->tecnicas->where('fuente', 'bekho')
            ->filter(fn ($t) => $t->categoria === CategoriaTecnica::Patada);

        expect($bekho->pluck('nombre')->sort()->values()->all())
            ->toEqual(collect($patadas)->sort()->values()->all());
    }

    // Ya no quedan patadas BEKHO por banda (todas tienen su cinturón).
    expect(Tecnica::where('fuente', 'bekho')->where('categoria', CategoriaTecnica::Patada->value)
        ->whereNull('cinturon')->count())->toBe(0);

    // "Giro Mariposa" era una conjetura de banda: ya no existe como patada BEKHO.
    expect(Tecnica::where('fuente', 'bekho')->where('nombre', 'Giro Mariposa')->exists())->toBeFalse();
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
