<?php

use App\Models\Prueba;
use App\Models\User;
use Database\Seeders\CatalogosFederacionSeeder;
use Database\Seeders\CompetenciaSeeder;
use Database\Seeders\FederacionesSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(FederacionesSeeder::class);
    $this->seed(CatalogosFederacionSeeder::class);
    $this->seed(CompetenciaSeeder::class);
    $this->seed(RolesPermisosSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

/** Usuario con el rol dado (el alumno solo tiene "ver programas"). */
function usuarioHojas(string $rol): User
{
    $user = User::create([
        'name' => ucfirst($rol), 'email' => $rol.'-hojas@bekho.cl',
        'password' => bcrypt('secreto'), 'activo' => true,
    ]);
    $user->assignRole($rol);

    return $user;
}

test('el índice lista las tres hojas', function () {
    $this->actingAs(usuarioHojas('instructor'))
        ->get(route('practica.planillas.index'))
        ->assertOk()
        ->assertSee('Fórmula y Armas')
        ->assertSee('Sparring')
        ->assertSee('Recuento de medallas');
});

test('la hoja de fórmula imprime los criterios de la prueba y las 16 líneas', function () {
    $formas = Prueba::where('modalidad', 'formas')->first();

    $this->actingAs(usuarioHojas('instructor'))
        ->get(route('practica.planillas.formula', $formas))
        ->assertOk()
        ->assertSee('Fórmula y Armas')
        // Criterios desde criterios_prueba, con su papel de juez.
        ->assertSee('Juez A')
        ->assertSee('Patadas y posiciones')
        ->assertSee('Juez central')
        ->assertSee('Golpes y defensas')
        // Encabezado y cierre de la planilla.
        ->assertSee('N.º de pista:')
        ->assertSee('Competidores negros')
        ->assertSee('Planillero')
        ->assertSeeInOrder(['1.º lugar', '2.º lugar', '3.º lugar'])
        ->assertSeeInOrder(['>1</td>', '>16</td>'], false);
});

test('la hoja de fórmula usa las casillas de los catálogos', function () {
    $formas = Prueba::where('modalidad', 'formas')->first();

    $this->actingAs(usuarioHojas('instructor'))
        ->get(route('practica.planillas.formula', $formas))
        ->assertOk()
        // Grupos de edad y categorías tal como los sembró la planilla oficial.
        ->assertSeeInOrder(['Tigers', '7 a 8', '50 a 59'])
        ->assertSeeInOrder(['Blanco', 'Rojo-Negro', 'Categoría Especial']);
});

test('el combate no tiene hoja de fórmula porque no se puntúa con jueces', function () {
    $combate = Prueba::where('modalidad', 'combate')->first();

    $this->actingAs(usuarioHojas('instructor'))
        ->get(route('practica.planillas.formula', $combate))
        ->assertNotFound();
});

test('la hoja de sparring trae la tabla de libres y la llave de 16', function () {
    $this->actingAs(usuarioHojas('instructor'))
        ->get(route('practica.planillas.sparring'))
        ->assertOk()
        ->assertSee('Tabla de libres')
        ->assertSee('Libres')
        ->assertSeeInOrder(['1.ª ronda', 'Cuartos', 'Semifinal', 'Final'])
        ->assertSee('Finalistas por 3.º y 4.º lugar')
        ->assertSee('Observaciones')
        ->assertSeeInOrder(['>1</td>', '>16</td>'], false);
});

test('la hoja de medallas sale con sus columnas de lugares', function () {
    $this->actingAs(usuarioHojas('instructor'))
        ->get(route('practica.planillas.medallas'))
        ->assertOk()
        ->assertSee('Recuento de medallas')
        ->assertSeeInOrder(['Grupo de edad', 'Categoría', 'Prueba', '1.º lugar', '2.º lugar', '3.º lugar']);
});

test('un alumno practica con las hojas; un apoderado no entra', function () {
    // El alumno tiene "ver programas": practica igual que planilleros y jueces.
    $this->actingAs(usuarioHojas('alumno'))
        ->get(route('practica.planillas.sparring'))
        ->assertOk();

    $this->actingAs(usuarioHojas('apoderado'))
        ->get(route('practica.planillas.sparring'))
        ->assertForbidden();
});
