<?php

use App\Enums\EstadoPlanilla;
use App\Livewire\Planillas\GestionPlanillas;
use App\Models\Planilla;
use App\Models\User;
use Database\Seeders\FederacionesSeeder;
use Database\Seeders\PlanillasSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(FederacionesSeeder::class);
    $this->seed(RolesPermisosSeeder::class);
    $this->seed(PlanillasSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function usuarioCon(string $rol): User
{
    $user = User::create([
        'name' => ucfirst($rol), 'email' => $rol.'@bekho.cl',
        'password' => bcrypt('secreto'), 'activo' => true,
    ]);
    $user->assignRole($rol);

    return $user;
}

test('el catálogo se siembra sin verificar y con las columnas del prototipo', function () {
    $examen = Planilla::where('nombre', 'Planilla de examen de grado')->first();
    $checklist = Planilla::where('nombre', 'Checklist técnico por requisito')->first();

    expect(Planilla::count())->toBe(6)
        ->and($examen->verificado)->toBeFalse()
        ->and($examen->estado)->toBe(EstadoPlanilla::Vigente)
        ->and($examen->etiquetaVersion())->toBe('v4')
        ->and($examen->columnas->pluck('titulo')->all())
        ->toBe(['Forma', 'Técnica', 'Defensa', 'Teoría', 'Asistencia'])
        // El prototipo la describía en vez de enumerar casillas: no se inventan.
        ->and($checklist->columnas)->toBeEmpty();
});

test('el seeder es idempotente y no duplica columnas', function () {
    $this->seed(PlanillasSeeder::class);

    expect(Planilla::count())->toBe(6)
        ->and(Planilla::where('nombre', 'Planilla de examen de grado')->first()->columnas)->toHaveCount(5);
});

test('el instructor ve el catálogo pero no puede editarlo', function () {
    Livewire::actingAs(usuarioCon('instructor'))
        ->test(GestionPlanillas::class)
        ->assertViewHas('puedeEditar', false)
        ->assertSee('Planilla de examen de grado')
        ->assertDontSee('Nueva planilla');
});

test('la dirección puede crear una planilla con sus columnas', function () {
    Livewire::actingAs(usuarioCon('direccion'))
        ->test(GestionPlanillas::class)
        ->assertViewHas('puedeEditar', true)
        ->call('nueva')
        ->set('nombre', 'Planilla de prueba')
        ->set('uso', 'Torneo')
        ->set('estado', EstadoPlanilla::Vigente->value)
        ->set('version', 2)
        ->set('filas', 10)
        ->set('columnas', ['Competidor', '  ', 'Puntaje'])
        ->call('guardar')
        ->assertHasNoErrors();

    $planilla = Planilla::where('nombre', 'Planilla de prueba')->first();

    // La columna vacía se descarta y el orden queda 1..n.
    expect($planilla->columnas->pluck('titulo')->all())->toBe(['Competidor', 'Puntaje'])
        ->and($planilla->columnas->pluck('orden')->all())->toBe([1, 2])
        ->and($planilla->filas)->toBe(10);
});

test('un rol sin gestionar programas no puede guardar aunque llame al método', function () {
    Livewire::actingAs(usuarioCon('instructor'))
        ->test(GestionPlanillas::class)
        ->set('nombre', 'Colada')
        ->call('guardar')
        ->assertForbidden();
});

test('imprimir devuelve la grilla en blanco con una fila por cada línea', function () {
    $planilla = Planilla::where('nombre', 'Planilla de examen de grado')->first();
    $planilla->update(['filas' => 3]);

    $this->actingAs(usuarioCon('instructor'))
        ->get(route('planillas.imprimir', $planilla))
        ->assertOk()
        ->assertSee('Planilla de examen de grado')
        ->assertSee('Asistencia')
        ->assertSee('Instructor:')
        // 3 filas numeradas + la cabecera.
        ->assertSeeInOrder(['<th class="num">#</th>', '>1</td>', '>2</td>', '>3</td>'], false);
});

test('una planilla sin columnas no se puede imprimir', function () {
    $checklist = Planilla::where('nombre', 'Checklist técnico por requisito')->first();

    $this->actingAs(usuarioCon('instructor'))
        ->get(route('planillas.imprimir', $checklist))
        ->assertNotFound();
});
