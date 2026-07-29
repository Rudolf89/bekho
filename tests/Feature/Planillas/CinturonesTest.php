<?php

use App\Enums\EscalaGrado;
use App\Enums\TipoGrado;
use App\Livewire\Planillas\Cinturones;
use App\Models\Academia;
use App\Models\Grado;
use App\Models\Tecnica;
use App\Models\User;
use Database\Seeders\GradosSeeder;
use Database\Seeders\GradoTecnicaSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Database\Seeders\TecnicasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    $this->seed(GradosSeeder::class);
    $this->seed(TecnicasSeeder::class);
    $this->seed(GradoTecnicaSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->bekho = Academia::where('nombre', 'BEKHO Power Academy')->first();
});

// --- Tipo (recomendado / decidido / dan) ------------------------------------

test('los grados distinguen recomendado, decidido y dan', function () {
    // For Kids: cada color tiene su par recomendado/decidido.
    $rojoRec = Grado::porEscala(EscalaGrado::ForKids)->where('nombre', 'Rojo Recomendado')->first();
    $rojoDec = Grado::porEscala(EscalaGrado::ForKids)->where('nombre', 'Rojo Decidido')->first();

    expect($rojoRec->tipo)->toBe(TipoGrado::Recomendado)
        ->and($rojoRec->esRecomendado())->toBeTrue()
        ->and($rojoDec->tipo)->toBe(TipoGrado::Decidido)
        ->and($rojoDec->esDecidido())->toBeTrue();

    // Adultos solo tiene el "decidido" de cada color.
    expect(Grado::porEscala(EscalaGrado::Adultos)->where('nombre', 'Rojo Decidido')->first()->tipo)
        ->toBe(TipoGrado::Decidido);
});

// --- Franjas -----------------------------------------------------------------

test('los danes llevan una franja por grado y los colores ninguna', function () {
    $tercerDan = Grado::porEscala(EscalaGrado::Adultos)->where('nombre', '3º Dan')->first();
    expect($tercerDan->tipo)->toBe(TipoGrado::Dan)
        ->and($tercerDan->franjas)->toBe(3);

    $blanco = Grado::porEscala(EscalaGrado::Adultos)->where('nombre', 'Blanco')->first();
    expect($blanco->franjas)->toBe(0);
});

// --- Enlace técnica ↔ cinturón ----------------------------------------------

test('cada técnica con cinturón queda enlazada a los grados de su color', function () {
    expect(DB::table('grado_tecnica')->count())->toBeGreaterThan(0);

    // Una técnica de "Blanco" enlaza a los grados Blanco de todas las escalas.
    $blancoAdultos = Grado::porEscala(EscalaGrado::Adultos)->where('color', 'Blanco')->first();
    expect($blancoAdultos->tecnicas()->count())->toBeGreaterThan(0);
});

test('la normalización de grafías enlaza Morado con el grado Púrpura', function () {
    // La biblioteca etiqueta "Morado"; el catálogo de grados usa "Púrpura".
    $purpura = Grado::where('color', 'Púrpura')->first();
    $tecnicasMorado = Tecnica::where('cinturon', 'Morado')->count();

    if ($tecnicasMorado > 0) {
        expect($purpura->tecnicas()->count())->toBeGreaterThan(0);
    }

    // Ninguna técnica quedó sin enlazar por diferencia de grafía conocida.
    expect(Tecnica::where('cinturon', 'Morado')->whereDoesntHave('grados')->count())->toBe(0)
        ->and(Tecnica::where('cinturon', 'Camuflaje')->whereDoesntHave('grados')->count())->toBe(0)
        ->and(Tecnica::where('cinturon', 'Marrón')->whereDoesntHave('grados')->count())->toBe(0);
});

// --- Vista -------------------------------------------------------------------

test('la página de cinturones muestra la escala y su significado', function () {
    $instructor = User::factory()->create(['academia_id' => $this->bekho->id]);
    $instructor->assignRole('instructor');

    Livewire::actingAs($instructor)->test(Cinturones::class)
        ->set('escala', 'adultos')
        ->assertSee('Blanco')
        ->assertSee('Decidido');
});

test('la página de cinturones exige el permiso de gestionar planillas', function () {
    $instructor = User::factory()->create(['academia_id' => $this->bekho->id]);
    $instructor->assignRole('instructor');
    $apoderado = User::factory()->create(['academia_id' => $this->bekho->id]);
    $apoderado->assignRole('apoderado');

    $this->actingAs($instructor)->get(route('cinturones.index'))->assertOk();
    $this->actingAs($apoderado)->get(route('cinturones.index'))->assertForbidden();
});
