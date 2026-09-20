<?php

use App\Livewire\Cuestionarios\EditarCuestionario;
use App\Livewire\Cuestionarios\RendirCuestionario;
use App\Models\Cuestionario;
use App\Models\Grupo;
use App\Models\IntentoCuestionario;
use App\Models\User;
use App\Support\Tenancy\Grupo as Tenant;
use Database\Seeders\CuestionariosSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    $this->seed(CuestionariosSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->bekho = Grupo::where('nombre', 'BEKHO Power Academy')->first();
});

afterEach(fn () => Tenant::olvidar());

function usuarioCuestionario(string $rol, ?int $grupoId): User
{
    $exige2fa = in_array($rol, config('bekho.2fa_obligatorio_para', []), true);
    $user = User::factory()->create([
        'grupo_id' => $grupoId,
        'two_factor_confirmed_at' => $exige2fa ? now() : null,
    ]);
    $user->assignRole($rol);

    return $user;
}

// --- Siembra / transversalidad ----------------------------------------------

test('se siembran los cuestionarios del banco de juez', function () {
    // 4 del banco de Juez ATA + 1 de la prueba escrita Legacy N3.
    expect(Cuestionario::count())->toBe(5);

    $n1 = Cuestionario::where('titulo', 'Examen de Juez ATA — Nivel 1')->first();
    expect($n1)->not->toBeNull()
        ->and($n1->preguntas()->count())->toBe(10)
        // Cada pregunta tiene al menos una opción correcta.
        ->and($n1->preguntas->every(fn ($p) => count($p->idsCorrectos()) >= 1))->toBeTrue();
});

test('los cuestionarios son transversales (catálogo compartido, sin grupo)', function () {
    $otra = Grupo::create(['nombre' => 'OTRA', 'activo' => true]);

    Tenant::set($this->bekho->id);
    $desdeBekho = Cuestionario::count();

    Tenant::set($otra->id);
    $desdeOtra = Cuestionario::count();

    expect($desdeBekho)->toBe(5)->and($desdeOtra)->toBe(5);
});

// --- Autocorrección ----------------------------------------------------------

test('rendir un cuestionario autocorrige, puntúa y guarda el intento', function () {
    $user = usuarioCuestionario('instructor', $this->bekho->id);
    Tenant::set($this->bekho->id);

    $cuestionario = Cuestionario::where('titulo', 'Examen de Juez ATA — Nivel 1')->first()->load('preguntas.opciones');

    // Elige la opción correcta de cada pregunta.
    $seleccion = [];
    foreach ($cuestionario->preguntas as $pregunta) {
        $seleccion[$pregunta->id] = $pregunta->idsCorrectos();
    }

    Livewire::actingAs($user)->test(RendirCuestionario::class, ['cuestionario' => $cuestionario])
        ->set('seleccion', $seleccion)
        ->call('enviar')
        ->assertSet('finalizado', true)
        ->assertSet('porcentaje', 100)
        ->assertSet('aprobado', true);

    $intento = IntentoCuestionario::where('user_id', $user->id)->first();
    expect($intento)->not->toBeNull()
        ->and($intento->correctas)->toBe(10)
        ->and($intento->total)->toBe(10)
        ->and($intento->aprobado)->toBeTrue()
        ->and($intento->respuestas()->count())->toBe(10);
});

test('un intento sin respuestas puntúa 0 y no aprueba', function () {
    $user = usuarioCuestionario('alumno', $this->bekho->id);
    Tenant::set($this->bekho->id);

    $cuestionario = Cuestionario::where('titulo', 'Examen de Juez ATA — Nivel 1')->first();

    Livewire::actingAs($user)->test(RendirCuestionario::class, ['cuestionario' => $cuestionario])
        ->call('enviar')
        ->assertSet('porcentaje', 0)
        ->assertSet('aprobado', false);
});

test('una pregunta de respuesta múltiple exige el conjunto exacto', function () {
    Tenant::set($this->bekho->id);

    $cuestionario = Cuestionario::create(['titulo' => 'Multi', 'activo' => true, 'umbral_aprobacion' => 50]);
    $pregunta = $cuestionario->preguntas()->create(['enunciado' => '¿Cuáles?', 'orden' => 0]);
    $a = $pregunta->opciones()->create(['texto' => 'A', 'correcta' => true, 'orden' => 0]);
    $b = $pregunta->opciones()->create(['texto' => 'B', 'correcta' => true, 'orden' => 1]);
    $pregunta->opciones()->create(['texto' => 'C', 'correcta' => false, 'orden' => 2]);
    $pregunta->load('opciones');

    expect($pregunta->esMultiple())->toBeTrue()
        ->and($pregunta->esCorrecta([$a->id, $b->id]))->toBeTrue()
        ->and($pregunta->esCorrecta([$a->id]))->toBeFalse()          // incompleta
        ->and($pregunta->esCorrecta([$a->id, $b->id, $a->id]))->toBeTrue(); // duplicados no afectan
});

// --- Editor (examinador) -----------------------------------------------------

test('un examinador crea un cuestionario con preguntas y opciones', function () {
    $examinador = usuarioCuestionario('instructor', $this->bekho->id);

    Livewire::actingAs($examinador)->test(EditarCuestionario::class)
        ->set('titulo', 'Regla básica de arbitraje')
        ->set('area', 'Arbitraje')
        ->set('preguntas', [[
            'id' => null,
            'enunciado' => '¿Cuántos jueces en fórmula?',
            'explicacion' => 'Tres jueces y un planillero.',
            'nota' => '',
            'opciones' => [
                ['id' => null, 'texto' => '3 jueces y un planillero', 'correcta' => true],
                ['id' => null, 'texto' => '2 jueces', 'correcta' => false],
            ],
        ]])
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertRedirect(route('cuestionarios.index'));

    $c = Cuestionario::where('titulo', 'Regla básica de arbitraje')->first();
    expect($c)->not->toBeNull()
        ->and($c->preguntas()->count())->toBe(1)
        ->and($c->preguntas->first()->opciones()->count())->toBe(2)
        ->and($c->preguntas->first()->idsCorrectos())->toHaveCount(1);
});

test('el editor exige al menos una opción correcta por pregunta', function () {
    $examinador = usuarioCuestionario('instructor', $this->bekho->id);

    Livewire::actingAs($examinador)->test(EditarCuestionario::class)
        ->set('titulo', 'Sin correcta')
        ->set('preguntas', [[
            'id' => null, 'enunciado' => 'X', 'explicacion' => '', 'nota' => '',
            'opciones' => [
                ['id' => null, 'texto' => 'A', 'correcta' => false],
                ['id' => null, 'texto' => 'B', 'correcta' => false],
            ],
        ]])
        ->call('guardar')
        ->assertHasErrors('preguntas.0.correcta');

    expect(Cuestionario::where('titulo', 'Sin correcta')->exists())->toBeFalse();
});

// --- Permisos ----------------------------------------------------------------

test('rendir requiere el permiso, gestionar es del examinador', function () {
    $alumno = usuarioCuestionario('alumno', $this->bekho->id);        // rendir sí, gestionar no
    $apoderado = usuarioCuestionario('apoderado', $this->bekho->id);  // ninguno
    $instructor = usuarioCuestionario('instructor', $this->bekho->id); // ambos

    $this->actingAs($alumno)->get(route('cuestionarios.index'))->assertOk();
    $this->actingAs($alumno)->get(route('cuestionarios.crear'))->assertForbidden();

    $this->actingAs($apoderado)->get(route('cuestionarios.index'))->assertForbidden();

    $this->actingAs($instructor)->get(route('cuestionarios.crear'))->assertOk();
});
