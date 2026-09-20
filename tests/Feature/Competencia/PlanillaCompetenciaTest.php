<?php

use App\Enums\PapelJuez;
use App\Livewire\Competencia\PlanillasCompetencia;
use App\Livewire\Competencia\VerPlanillaCompetencia;
use App\Models\CompetidorPlanilla;
use App\Models\CriterioPrueba;
use App\Models\PlanillaCompetencia;
use App\Models\Prueba;
use App\Models\PuntajePlanilla;
use App\Models\User;
use App\Services\ServicioPlanillaCompetencia;
use Database\Seeders\CatalogosFederacionSeeder;
use Database\Seeders\CompetenciaSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    $this->seed(CatalogosFederacionSeeder::class);
    $this->seed(CompetenciaSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->prueba = Prueba::with('criterios')->orderBy('orden')->first();
    $this->planilla = PlanillaCompetencia::create(['prueba_id' => $this->prueba->id, 'estado' => 'borrador']);
    $this->competidor = CompetidorPlanilla::create(['planilla_id' => $this->planilla->id, 'orden' => 1, 'nombre' => 'Ficticio']);
});

// --- Servicio ----------------------------------------------------------------

test('registra un puntaje válido (9.1–9.9) y calcula el dígito', function () {
    $criterio = $this->prueba->criterios->first();

    $puntaje = app(ServicioPlanillaCompetencia::class)->registrarPuntaje($this->competidor, $criterio, 97);

    expect($puntaje->puntaje)->toBe(97)
        ->and($puntaje->digito())->toBe(7);
});

test('rechaza un puntaje fuera de rango', function () {
    $criterio = $this->prueba->criterios->first();

    expect(fn () => app(ServicioPlanillaCompetencia::class)->registrarPuntaje($this->competidor, $criterio, 100))
        ->toThrow(ValidationException::class);
});

test('el 0 (penalización) solo se admite en criterios que lo permiten', function () {
    $servicio = app(ServicioPlanillaCompetencia::class);
    $central = $this->prueba->criterios->firstWhere('papel_juez', PapelJuez::Central); // permite_cero
    $lateral = $this->prueba->criterios->first(fn (CriterioPrueba $c) => ! $c->permite_cero);

    // El central permite 0 (penalización); un criterio lateral no.
    expect($servicio->registrarPuntaje($this->competidor, $central, 0)->puntaje)->toBe(0)
        ->and(fn () => $servicio->registrarPuntaje($this->competidor, $lateral, 0))
        ->toThrow(ValidationException::class);
});

test('el ranking ordena por total descendente (suma de dígitos)', function () {
    $servicio = app(ServicioPlanillaCompetencia::class);
    $criterio = $this->prueba->criterios->first();

    $otro = CompetidorPlanilla::create(['planilla_id' => $this->planilla->id, 'orden' => 2, 'nombre' => 'Segundo']);

    $servicio->registrarPuntaje($this->competidor, $criterio, 93); // dígito 3
    $servicio->registrarPuntaje($otro, $criterio, 98);             // dígito 8

    $ranking = $servicio->ranking($this->planilla->fresh());

    expect($ranking->first()->nombre)->toBe('Segundo')
        ->and($ranking->first()->total)->toBe(8);
});

// --- Permisos y componentes --------------------------------------------------

function usuarioCompetencia(string $rol): User
{
    $exige2fa = in_array($rol, config('bekho.2fa_obligatorio_para', []), true);
    $u = User::factory()->create(['two_factor_confirmed_at' => $exige2fa ? now() : null]);
    $u->assignRole($rol);

    return $u;
}

test('un instructor puede abrir las planillas de competencia', function () {
    $this->actingAs(usuarioCompetencia('instructor'))
        ->get(route('competencia.index'))->assertOk();
});

test('un administrativo no puede abrir las planillas de competencia', function () {
    $this->actingAs(usuarioCompetencia('administrativo'))
        ->get(route('competencia.index'))->assertForbidden();
});

test('se crea una planilla desde el componente', function () {
    Livewire::actingAs(usuarioCompetencia('direccion'))->test(PlanillasCompetencia::class)
        ->call('nueva')
        ->set('prueba_id', (string) $this->prueba->id)
        ->call('crear')
        ->assertHasNoErrors()
        ->assertRedirect();

    expect(PlanillaCompetencia::where('prueba_id', $this->prueba->id)->count())->toBe(2);
});

test('se agregan competidores, jueces y puntajes desde el detalle', function () {
    $criterio = $this->prueba->criterios->first();

    Livewire::actingAs(usuarioCompetencia('direccion'))->test(VerPlanillaCompetencia::class, ['planilla' => $this->planilla])
        ->set('juezPapel', 'central')->set('juezNombre', 'Juez X')->call('agregarJuez')->assertHasNoErrors()
        ->set('compNombre', 'Nuevo')->call('agregarCompetidor')->assertHasNoErrors();

    $nuevo = CompetidorPlanilla::where('nombre', 'Nuevo')->first();

    Livewire::actingAs(usuarioCompetencia('direccion'))->test(VerPlanillaCompetencia::class, ['planilla' => $this->planilla])
        ->set("puntajes.{$nuevo->id}.{$criterio->id}", '7')
        ->call('guardarPuntaje', $nuevo->id, $criterio->id)
        ->assertHasNoErrors();

    expect(PuntajePlanilla::where('competidor_planilla_id', $nuevo->id)->where('criterio_prueba_id', $criterio->id)->first()->puntaje)->toBe(97)
        ->and($this->planilla->jueces()->count())->toBe(1);
});
