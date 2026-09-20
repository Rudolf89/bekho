<?php

use App\Enums\EstadoIntento;
use App\Enums\EstadoLegacy;
use App\Livewire\Programas\GestionInscripciones;
use App\Models\Cuestionario;
use App\Models\EtapaPrograma;
use App\Models\Grupo;
use App\Models\InscripcionPrograma;
use App\Models\IntentoCuestionario;
use App\Models\Matricula;
use App\Models\Persona;
use App\Models\Programa;
use App\Models\RequisitoEtapa;
use App\Models\Sede;
use App\Models\User;
use App\Support\Tenancy\Grupo as Tenant;
use Database\Seeders\EtapasProgramaSeeder;
use Database\Seeders\FederacionesSeeder;
use Database\Seeders\GradosSeeder;
use Database\Seeders\ProgramasSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    $this->seed(FederacionesSeeder::class);
    $this->seed(ProgramasSeeder::class);
    $this->seed(GradosSeeder::class);
    $this->seed(EtapasProgramaSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->bekho = Grupo::where('nombre', 'BEKHO Power Academy')->first();
    $this->legacy = Programa::where('nombre', 'Legacy')->first();
    Tenant::set($this->bekho->id);
});

afterEach(fn () => Tenant::olvidar());

function usuarioLegacy(string $rol, int $grupoId): User
{
    $persona = Persona::create(['nombres' => 'U '.uniqid(), 'fecha_nacimiento' => now()->subYears(30)]);
    $u = User::factory()->create(['grupo_id' => $grupoId, 'persona_id' => $persona->id]);
    $u->assignRole($rol);

    return $u;
}

/** Inscripción a Legacy en la etapa dada (por orden). */
function inscribirEnPrograma(Persona $persona, EtapaPrograma $etapa): InscripcionPrograma
{
    return InscripcionPrograma::create([
        'persona_id' => $persona->id, 'programa_id' => $etapa->programa_id,
        'etapa_actual_id' => $etapa->id, 'estado' => EstadoLegacy::EnCurso->value, 'fecha_ingreso' => now(),
    ]);
}

test('el programa Legacy tiene 3 etapas de 100 h con edades 13/16/18', function () {
    $etapas = $this->legacy->etapas()->whereNotNull('horas_requeridas')->orderBy('orden')->get();

    expect($etapas)->toHaveCount(3)
        ->and($etapas->pluck('edad_minima')->all())->toBe([13, 16, 18])
        ->and($etapas->every(fn ($e) => $e->horas_requeridas === 100))->toBeTrue()
        ->and($etapas->every(fn ($e) => $e->requisitos()->count() > 0))->toBeTrue();
});

test('acumular 100 h marca las horas como completas', function () {
    $persona = Persona::create(['nombres' => 'Formando', 'fecha_nacimiento' => now()->subYears(20)]);
    $etapa = $this->legacy->etapas()->orderBy('orden')->first();
    $inscripcion = inscribirEnPrograma($persona, $etapa);

    $inscripcion->horas()->create(['fecha' => now(), 'horas' => 60, 'origen' => 'manual']);
    expect($inscripcion->horasCompletas())->toBeFalse();

    $inscripcion->horas()->create(['fecha' => now(), 'horas' => 40, 'origen' => 'manual']);
    expect((float) $inscripcion->fresh()->horasAcumuladas())->toBe(100.0)
        ->and($inscripcion->fresh()->horasCompletas())->toBeTrue();
});

test('la dirección no puede aprobar sin cumplir horas y requisitos', function () {
    $direccion = usuarioLegacy('direccion', $this->bekho->id);
    $persona = Persona::create(['nombres' => 'Formando', 'fecha_nacimiento' => now()->subYears(20)]);
    $etapa = $this->legacy->etapas()->orderBy('orden')->first();
    $inscripcion = inscribirEnPrograma($persona, $etapa);
    $inscripcion->horas()->create(['fecha' => now(), 'horas' => 100, 'origen' => 'manual']); // horas ok, requisitos no

    Livewire::actingAs($direccion)->test(GestionInscripciones::class)
        ->set('inscripcionId', $inscripcion->id)
        ->call('aprobar');

    expect($inscripcion->fresh()->etapa_actual_id)->toBe($etapa->id);
});

test('la dirección aprueba el ascenso cuando todo está cumplido y avanza de etapa', function () {
    $direccion = usuarioLegacy('direccion', $this->bekho->id);
    $persona = Persona::create(['nombres' => 'Formando', 'fecha_nacimiento' => now()->subYears(20)]);
    $etapas = $this->legacy->etapas()->whereNotNull('horas_requeridas')->orderBy('orden')->get();
    $etapa = $etapas->first();
    $inscripcion = inscribirEnPrograma($persona, $etapa);
    $inscripcion->horas()->create(['fecha' => now(), 'horas' => 100, 'origen' => 'manual']);

    foreach ($etapa->requisitos as $r) {
        $inscripcion->cumplimientos()->create(['requisito_etapa_id' => $r->id, 'cumplido_at' => now()]);
    }

    expect($inscripcion->fresh()->puedeAprobar())->toBeTrue();

    Livewire::actingAs($direccion)->test(GestionInscripciones::class)
        ->set('inscripcionId', $inscripcion->id)
        ->call('aprobar');

    $inscripcion->refresh();
    expect($inscripcion->ascensos()->count())->toBe(1)
        ->and($inscripcion->etapa_actual_id)->toBe($etapas[1]->id); // avanzó a la etapa 2
});

test('el instructor del alumno puede aprobar; uno ajeno no', function () {
    $sede = Sede::create(['grupo_id' => $this->bekho->id, 'nombre' => 'Central', 'activo' => true]);
    $instructor = usuarioLegacy('instructor', $this->bekho->id);
    $ajeno = usuarioLegacy('instructor', $this->bekho->id);

    $persona = Persona::create(['nombres' => 'Alumno', 'fecha_nacimiento' => now()->subYears(16)]);
    // Matrícula activa con ese instructor.
    Matricula::create([
        'grupo_id' => $this->bekho->id, 'persona_id' => $persona->id, 'sede_id' => $sede->id,
        'grupo_etario' => 'for_kids', 'estado' => 'activa', 'fecha_ingreso' => now(),
        'instructor_persona_id' => $instructor->persona_id,
    ]);

    $etapa = $this->legacy->etapas()->orderBy('orden')->first();
    $inscripcion = inscribirEnPrograma($persona, $etapa);

    // El instructor ajeno no está autorizado a aprobar.
    Livewire::actingAs($ajeno)->test(GestionInscripciones::class)
        ->set('inscripcionId', $inscripcion->id)
        ->call('aprobar')
        ->assertForbidden();

    // El instructor del alumno sí (aunque falten horas, pasa el control de autorización).
    Livewire::actingAs($instructor)->test(GestionInscripciones::class)
        ->set('inscripcionId', $inscripcion->id)
        ->call('aprobar')
        ->assertOk();
});

test('un requisito enlazado a un cuestionario se cumple al aprobar el intento', function () {
    $persona = Persona::create(['nombres' => 'Formando', 'fecha_nacimiento' => now()->subYears(20)]);
    $user = User::factory()->create(['grupo_id' => $this->bekho->id, 'persona_id' => $persona->id]);
    $etapa = $this->legacy->etapas()->orderBy('orden')->first();
    $inscripcion = inscribirEnPrograma($persona, $etapa);

    $cuestionario = Cuestionario::create(['titulo' => 'Prueba escrita X', 'activo' => true, 'umbral_aprobacion' => 80]);
    $requisito = RequisitoEtapa::create([
        'etapa_programa_id' => $etapa->id, 'descripcion' => 'Prueba escrita aprobada',
        'tipo' => 'cuestionario', 'cuestionario_id' => $cuestionario->id, 'orden' => 99,
    ]);

    expect($inscripcion->cumpleRequisito($requisito))->toBeFalse();

    IntentoCuestionario::create([
        'grupo_id' => $this->bekho->id, 'user_id' => $user->id, 'cuestionario_id' => $cuestionario->id,
        'correctas' => 9, 'total' => 10, 'porcentaje' => 90, 'aprobado' => true,
        'estado' => EstadoIntento::Aprobado->value, 'finalizado_at' => now(),
    ]);

    expect($inscripcion->fresh()->cumpleRequisito($requisito->fresh()))->toBeTrue();
});

test('la gestión de inscripciones exige el permiso gestionar legacy', function () {
    $instructor = usuarioLegacy('instructor', $this->bekho->id);
    $apoderado = usuarioLegacy('apoderado', $this->bekho->id);

    Tenant::olvidar();
    $this->actingAs($instructor)->get(route('programas.gestion'))->assertOk();
    $this->actingAs($apoderado)->get(route('programas.gestion'))->assertForbidden();
});
