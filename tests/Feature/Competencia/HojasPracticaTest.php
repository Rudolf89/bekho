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

test('el índice lista las cuatro hojas', function () {
    $this->actingAs(usuarioHojas('instructor'))
        ->get(route('practica.planillas.index'))
        ->assertOk()
        ->assertSee('Fórmula y Armas')
        ->assertSee('Sparring')
        ->assertSee('Combat Weapons')
        ->assertSee('Recuento de medallas');
});

test('la hoja de fórmula trae las dos pruebas lado a lado con sus criterios', function () {
    $this->actingAs(usuarioHojas('instructor'))
        ->get(route('practica.planillas.formula'))
        ->assertOk()
        // Las dos tablas de la planilla, en el orden del catálogo.
        ->assertSeeInOrder(['Formula Tradicional', 'Armas Tradicionales'])
        // Criterios desde criterios_prueba, con el texto de la planilla.
        ->assertSee('Patadas y Posiciones')
        ->assertSee('Golpes y Defensas')
        ->assertSee('Memorización, Transición, Apariencia, Actitud')
        ->assertSee('Tiempo, Fluidez, Precisión, Consistencia')
        // Encabezado y cierre de la planilla.
        ->assertSee('Cinturones Negros')
        ->assertSee('N.º de pista:')
        ->assertSee('Nivel · País')
        ->assertSee('Planillero')
        ->assertSeeInOrder(['1er lugar', '2do lugar', '3er lugar'])
        ->assertSeeInOrder(['>1.-</td>', '>16.-</td>'], false);
});

test('solo la tabla de fórmula lleva edad y país', function () {
    // La planilla lo dice al pie: la edad es solo para cinturones negros y el
    // país para el Panamericano; la tabla de armas no trae esas columnas.
    $html = $this->actingAs(usuarioHojas('instructor'))
        ->get(route('practica.planillas.formula'))
        ->assertOk()
        ->getContent();

    expect(substr_count($html, '>Edad</th>'))->toBe(1)
        ->and(substr_count($html, '>País</th>'))->toBe(1);
});

test('la hoja de fórmula usa las casillas de los catálogos', function () {
    $this->actingAs(usuarioHojas('instructor'))
        ->get(route('practica.planillas.formula'))
        ->assertOk()
        // Grupos de edad y categorías tal como los sembró la planilla oficial.
        ->assertSeeInOrder(['TIGERS', '7 a 8 años', '50 a 59 años'])
        ->assertSeeInOrder(['Blanco', 'Rojo-Negro', 'Ctg. Especial']);
});

test('el sparring no aparece en la hoja de fórmula: no se puntúa con jueces', function () {
    expect(Prueba::where('modalidad', 'combate')->first()->criterios)->toBeEmpty();

    $this->actingAs(usuarioHojas('instructor'))
        ->get(route('practica.planillas.formula'))
        ->assertOk()
        ->assertDontSee('<th colspan="2" style="text-align:center">Sparring</th>', false);
});

test('la hoja de sparring trae la tabla de libres y la llave de 16', function () {
    $this->actingAs(usuarioHojas('instructor'))
        ->get(route('practica.planillas.sparring'))
        ->assertOk()
        ->assertSee('N.º de libres')
        // La tabla de libres, con los pares de la planilla (02→00 … 09→07).
        ->assertSeeInOrder(['>02</th>', '>09</th>', '>16</th>'], false)
        ->assertSeeInOrder(['>00</td>', '>07</td>', '>00</td>'], false)
        ->assertSeeInOrder(['Primera ronda', 'Segunda ronda', 'Semifinales', 'Final'])
        ->assertSee('Finalistas por 1er y 2do lugar')
        ->assertSee('Finalistas por 3er y 4to lugar')
        ->assertSee('Registro de firmas')
        ->assertSee('Observaciones')
        ->assertSeeInOrder(['>1.-</td>', '>16.-</td>'], false);
});

test('la hoja de medallas cuenta por pista los tres lugares y la participación', function () {
    $this->actingAs(usuarioHojas('instructor'))
        ->get(route('practica.planillas.medallas'))
        ->assertOk()
        ->assertSee('Recuento de medallas')
        ->assertSeeInOrder(['N.º pista', '1er lugar', '2do lugar', '3er lugar', 'Participación'])
        ->assertSee('N.º de medallas');
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

test('la llave agrupa las 16 líneas: 16 → 8 → 4 → 2', function () {
    $html = $this->actingAs(usuarioHojas('instructor'))
        ->get(route('practica.planillas.sparring'))
        ->assertOk()
        ->getContent();

    // Cada ronda cubre el doble de líneas que la anterior.
    expect(substr_count($html, 'rowspan="2"'))->toBe(8)
        ->and(substr_count($html, 'rowspan="4"'))->toBe(4)
        ->and(substr_count($html, 'rowspan="8"'))->toBe(2);
});

test('Combat Weapons es la hoja del sparring con otro título', function () {
    // La federación llena Combat Weapons con la misma hoja: cambia el título y
    // nada más, así que el cuerpo debe ser idéntico al del sparring.
    $usuario = usuarioHojas('instructor');

    $sparring = $this->actingAs($usuario)
        ->get(route('practica.planillas.sparring'))->assertOk()->getContent();

    $armas = $this->actingAs($usuario)
        ->get(route('practica.planillas.combat-weapons'))->assertOk()
        ->assertSee('<title>Combat Weapons</title>', false)
        ->assertSee('<h1>Combat Weapons</h1>', false)
        ->assertDontSee('Sección 2. Sparring')
        ->getContent();

    // Mismo cuerpo salvo el título y el encabezado. Se quitan los assets que
    // Livewire inyecta, que no son parte de la hoja.
    $limpia = fn (string $html) => trim((string) preg_replace(
        ['/<!-- Livewire (Styles|Scripts) -->.*?(<\/style>|<\/script>)/s', '/\s+/'],
        ['', ' '],
        $html,
    ));
    $normaliza = fn (string $html) => str_replace(
        ['<title>Sparring</title>', '<h1>Sección 2. Sparring</h1>'],
        ['<title>Combat Weapons</title>', '<h1>Combat Weapons</h1>'],
        $limpia($html),
    );

    expect($limpia($armas))->toBe($normaliza($sparring));
});
