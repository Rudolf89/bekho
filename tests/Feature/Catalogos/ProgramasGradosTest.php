<?php

use App\Enums\EscalaGrado;
use App\Enums\GrupoEtario;
use App\Models\CargoRango;
use App\Models\Grado;
use App\Models\Programa;
use App\Support\Tenancy\Academia as Tenant;
use Database\Seeders\CargosRangosSeeder;
use Database\Seeders\GradosSeeder;
use Database\Seeders\ProgramasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('el seeder crea los seis programas y es idempotente', function () {
    $this->seed(ProgramasSeeder::class);
    $this->seed(ProgramasSeeder::class); // segunda vez: no duplica

    expect(Programa::count())->toBe(6);
    expect(Programa::pluck('nombre')->all())->toContain(
        'Xtreme', 'Defense', 'Black Belt Club', 'Master Club', 'Leadership', 'Legacy',
    );
});

test('programas y grados son catálogos compartidos sin academia_id', function () {
    expect(Schema::hasColumn('programas', 'academia_id'))->toBeFalse();
    expect(Schema::hasColumn('grados', 'academia_id'))->toBeFalse();
});

test('el catálogo de programas no se filtra por academia activa', function () {
    $this->seed(ProgramasSeeder::class);

    Tenant::set(1);
    // Al no usar PerteneceAcademia, el catálogo se ve completo con tenant activo.
    expect(Programa::count())->toBe(6);
});

test('Leadership y Legacy coexisten como programa y como rango sin fusionarse', function () {
    $this->seed(ProgramasSeeder::class);
    $this->seed(CargosRangosSeeder::class);

    // Existen como PROGRAMA (la ruta)...
    expect(Programa::where('nombre', 'Legacy')->exists())->toBeTrue();
    expect(Programa::where('nombre', 'Leadership')->exists())->toBeTrue();

    // ...y el RANGO Legado (la meta) sigue intacto en cargos_rangos.
    expect(CargoRango::where('nombre', 'like', '%Legado%')->exists())->toBeTrue();
    expect(CargoRango::count())->toBe(7);
});

test('el grupo etario se sugiere pero no es automático en el solapamiento', function () {
    expect(GrupoEtario::sugerirPorEdad(5))->toBe(GrupoEtario::Tigers);
    expect(GrupoEtario::sugerirPorEdad(9))->toBe(GrupoEtario::ForKids);
    expect(GrupoEtario::sugerirPorEdad(20))->toBe(GrupoEtario::JovenesAdultos);
});

test('la escala de grados corresponde al grupo etario', function () {
    expect(EscalaGrado::paraGrupo(GrupoEtario::Tigers))->toBe(EscalaGrado::Tigers);
    expect(EscalaGrado::paraGrupo(GrupoEtario::ForKids))->toBe(EscalaGrado::ForKids);
    expect(EscalaGrado::paraGrupo(GrupoEtario::JovenesAdultos))->toBe(EscalaGrado::Adultos);
});

test('el seeder de grados crea las tres escalas y es idempotente', function () {
    $this->seed(GradosSeeder::class);
    $this->seed(GradosSeeder::class); // no duplica

    expect(Grado::porEscala(EscalaGrado::Tigers)->count())->toBe(18);
    expect(Grado::porEscala(EscalaGrado::ForKids)->count())->toBe(27); // 18 + 9 danes
    expect(Grado::porEscala(EscalaGrado::Adultos)->count())->toBe(19); // 10 + 9 danes

    // Tigers empieza con Blanco y luego Blanco Tortuga.
    $tigers = Grado::porEscala(EscalaGrado::Tigers)->ordenados()->pluck('nombre');
    expect($tigers->first())->toBe('Blanco');
    expect($tigers->get(1))->toBe('Blanco Tortuga');

    // Los danes van al final en For Kids y Adultos.
    expect(Grado::porEscala(EscalaGrado::Adultos)->ordenados()->pluck('nombre')->last())->toBe('9º Dan');
});

test('el scope porEscala filtra los grados de una escala', function () {
    Grado::create(['nombre' => 'Blanco', 'orden' => 1, 'escala' => 'adultos', 'activo' => true]);
    Grado::create(['nombre' => 'Tigre Blanco', 'orden' => 1, 'escala' => 'tigers', 'activo' => true]);

    expect(Grado::porEscala(EscalaGrado::Adultos)->count())->toBe(1);
    expect(Grado::porEscala(EscalaGrado::Tigers)->count())->toBe(1);
    expect(Grado::porEscala(EscalaGrado::Adultos)->first()->nombre)->toBe('Blanco');
});
