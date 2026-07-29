<?php

use App\Enums\EstadoIntento;
use App\Enums\EstadoLegacy;
use App\Livewire\Legacy\PanelLegacy;
use App\Models\Academia;
use App\Models\Cuestionario;
use App\Models\InscripcionLegacy;
use App\Models\IntentoCuestionario;
use App\Models\NivelLegacy;
use App\Models\RequisitoLegacy;
use App\Models\User;
use App\Support\Tenancy\Academia as Tenant;
use Database\Seeders\LegacySeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    $this->seed(LegacySeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->bekho = Academia::where('nombre', 'BEKHO Power Academy')->first();
    Tenant::set($this->bekho->id);
});

afterEach(fn () => Tenant::olvidar());

function usuarioLegacy(string $rol, int $academiaId): User
{
    $exige2fa = in_array($rol, config('bekho.2fa_obligatorio_para', []), true);
    $u = User::factory()->create([
        'academia_id' => $academiaId,
        'two_factor_confirmed_at' => $exige2fa ? now() : null,
    ]);
    $u->assignRole($rol);

    return $u;
}

// --- Catálogo ----------------------------------------------------------------

test('el catálogo Legacy tiene 3 niveles de 100 h con requisitos', function () {
    expect(NivelLegacy::count())->toBe(3);
    NivelLegacy::all()->each(fn ($n) => expect($n->horas_requeridas)->toBe(100)
        ->and($n->requisitos()->count())->toBeGreaterThan(0));
});

// --- Inscripción + horas -----------------------------------------------------

test('acumular 100 h marca las horas como completas', function () {
    $formando = usuarioLegacy('instructor', $this->bekho->id);
    $nivel = NivelLegacy::ordenados()->first();
    $inscripcion = InscripcionLegacy::create([
        'academia_id' => $this->bekho->id, 'user_id' => $formando->id, 'nivel_legacy_id' => $nivel->id,
        'estado' => EstadoLegacy::EnCurso->value,
    ]);

    $inscripcion->horas()->create(['fecha' => now(), 'horas' => 60]);
    expect($inscripcion->horasCompletas())->toBeFalse();

    $inscripcion->horas()->create(['fecha' => now(), 'horas' => 40]);
    expect($inscripcion->fresh()->horasAcumuladas())->toEqual(100.0)
        ->and($inscripcion->fresh()->horasCompletas())->toBeTrue();
});

// --- Ascenso -----------------------------------------------------------------

test('no se puede aprobar sin cumplir 100 h y todos los requisitos', function () {
    $direccion = usuarioLegacy('direccion', $this->bekho->id);
    $formando = usuarioLegacy('instructor', $this->bekho->id);
    $nivel = NivelLegacy::ordenados()->first();
    $inscripcion = InscripcionLegacy::create([
        'academia_id' => $this->bekho->id, 'user_id' => $formando->id, 'nivel_legacy_id' => $nivel->id,
        'estado' => EstadoLegacy::EnCurso->value,
    ]);
    $inscripcion->horas()->create(['fecha' => now(), 'horas' => 100]); // horas ok, requisitos no

    Livewire::actingAs($direccion)->test(PanelLegacy::class)
        ->set('inscripcionId', $inscripcion->id)
        ->call('aprobar');

    expect($inscripcion->fresh()->estado)->toBe(EstadoLegacy::EnCurso);
});

test('el licenciatario aprueba el ascenso cuando todo está cumplido', function () {
    $direccion = usuarioLegacy('direccion', $this->bekho->id);
    $formando = usuarioLegacy('instructor', $this->bekho->id);
    $nivel = NivelLegacy::ordenados()->first();
    $inscripcion = InscripcionLegacy::create([
        'academia_id' => $this->bekho->id, 'user_id' => $formando->id, 'nivel_legacy_id' => $nivel->id,
        'estado' => EstadoLegacy::EnCurso->value,
    ]);
    $inscripcion->horas()->create(['fecha' => now(), 'horas' => 100]);

    // Marca todos los requisitos manuales como cumplidos.
    foreach ($nivel->requisitos as $r) {
        $inscripcion->requisitosCumplidos()->attach($r->id, ['verificado_por' => $direccion->id, 'verificado_at' => now()]);
    }

    expect($inscripcion->fresh()->puedeAprobar())->toBeTrue();

    Livewire::actingAs($direccion)->test(PanelLegacy::class)
        ->set('inscripcionId', $inscripcion->id)
        ->call('aprobar');

    $inscripcion->refresh();
    expect($inscripcion->estado)->toBe(EstadoLegacy::Aprobado)
        ->and($inscripcion->aprobado_por)->toBe($direccion->id)
        ->and($inscripcion->fecha_aprobacion)->not->toBeNull();
});

test('un instructor no puede aprobar el ascenso (solo el licenciatario)', function () {
    $instructor = usuarioLegacy('instructor', $this->bekho->id);
    $formando = usuarioLegacy('instructor', $this->bekho->id);
    $nivel = NivelLegacy::ordenados()->first();
    $inscripcion = InscripcionLegacy::create([
        'academia_id' => $this->bekho->id, 'user_id' => $formando->id, 'nivel_legacy_id' => $nivel->id,
        'estado' => EstadoLegacy::EnCurso->value,
    ]);

    Livewire::actingAs($instructor)->test(PanelLegacy::class)
        ->set('inscripcionId', $inscripcion->id)
        ->call('aprobar')
        ->assertForbidden();
});

// --- Prueba escrita = cuestionario aprobado ---------------------------------

test('un requisito enlazado a un cuestionario se cumple al aprobar el intento', function () {
    $formando = usuarioLegacy('instructor', $this->bekho->id);
    $nivel = NivelLegacy::ordenados()->first();

    $cuestionario = Cuestionario::create(['titulo' => 'Prueba escrita Legacy', 'activo' => true, 'umbral_aprobacion' => 80]);
    $requisito = RequisitoLegacy::create([
        'nivel_legacy_id' => $nivel->id, 'texto' => 'Prueba escrita aprobada', 'cuestionario_id' => $cuestionario->id, 'orden' => 99,
    ]);

    $inscripcion = InscripcionLegacy::create([
        'academia_id' => $this->bekho->id, 'user_id' => $formando->id, 'nivel_legacy_id' => $nivel->id,
        'estado' => EstadoLegacy::EnCurso->value,
    ]);

    // Sin intento aprobado: no cumple.
    expect($inscripcion->cumpleRequisito($requisito))->toBeFalse();

    // Intento aprobado por el examinador: cumple automáticamente.
    IntentoCuestionario::create([
        'academia_id' => $this->bekho->id, 'user_id' => $formando->id, 'cuestionario_id' => $cuestionario->id,
        'correctas' => 9, 'total' => 10, 'porcentaje' => 90, 'aprobado' => true,
        'estado' => EstadoIntento::Aprobado->value, 'finalizado_at' => now(),
    ]);

    expect($inscripcion->fresh()->cumpleRequisito($requisito->fresh()))->toBeTrue();
});

// --- Permisos ----------------------------------------------------------------

test('el panel Legacy exige el permiso gestionar legacy', function () {
    $instructor = usuarioLegacy('instructor', $this->bekho->id);
    $apoderado = usuarioLegacy('apoderado', $this->bekho->id);

    Tenant::olvidar();
    $this->actingAs($instructor)->get(route('legacy.index'))->assertOk();
    $this->actingAs($apoderado)->get(route('legacy.index'))->assertForbidden();
});
