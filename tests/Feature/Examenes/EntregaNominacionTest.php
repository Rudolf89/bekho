<?php

use App\Enums\ResultadoExamen;
use App\Models\Convocatoria;
use App\Models\Grado;
use App\Models\Graduacion;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Matricula;
use App\Models\Persona;
use App\Models\User;
use App\Services\ServicioExamenes;
use App\Support\Tenancy\Grupo as Tenant;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->bekho = Grupo::where('nombre', 'BEKHO Power Academy')->first();
    Tenant::set($this->bekho->id);

    $this->servicio = app(ServicioExamenes::class);
    $this->rojo = Grado::create(['nombre' => 'Rojo', 'orden' => 16, 'escala' => 'adultos', 'color' => 'Rojo', 'activo' => true, 'requiere_nominacion' => false]);
    $this->dan = Grado::create(['nombre' => '1º Dan', 'orden' => 19, 'escala' => 'adultos', 'color' => 'Negro', 'activo' => true, 'requiere_nominacion' => true]);
});

afterEach(fn () => Tenant::olvidar());

function matriculaExamenN(int $grupoId, ?int $gradoId, string $nombre): Matricula
{
    $persona = Persona::create(['nombres' => $nombre, 'fecha_nacimiento' => now()->subYears(20), 'grado_id' => $gradoId]);

    return Matricula::create([
        'grupo_id' => $grupoId, 'persona_id' => $persona->id,
        'grupo_etario' => 'jovenes_adultos', 'estado' => 'activa', 'fecha_ingreso' => now(),
    ]);
}

function inscripcionN(Grupo $grupo, Matricula $m, Grado $origen, Grado $destino, User $instructor): Inscripcion
{
    $conv = Convocatoria::create(['grupo_id' => $grupo->id, 'nombre' => 'Examen', 'fecha' => now(), 'estado' => 'programada']);

    return Inscripcion::create([
        'grupo_id' => $grupo->id, 'convocatoria_id' => $conv->id, 'matricula_id' => $m->id,
        'grado_origen_id' => $origen->id, 'grado_destino_id' => $destino->id, 'instructor_id' => $instructor->id,
        'visto_bueno' => true,
    ]);
}

// --- Nominación --------------------------------------------------------------

test('nominar deja una nominación pendiente y aprobarla la habilita', function () {
    $persona = Persona::create(['nombres' => 'Aspirante', 'fecha_nacimiento' => now()->subYears(25)]);

    $nom = $this->servicio->nominar($persona, $this->dan);
    expect($nom->estado->value)->toBe('pendiente')
        ->and($this->servicio->tieneNominacionAprobada($persona, $this->dan))->toBeFalse();

    $this->servicio->resolverNominacion($nom, true);
    expect($this->servicio->tieneNominacionAprobada($persona, $this->dan))->toBeTrue();
});

test('no se gradúa a un grado que requiere nominación sin nominación aprobada', function () {
    $instructor = User::factory()->create(['grupo_id' => $this->bekho->id]);
    $matricula = matriculaExamenN($this->bekho->id, $this->rojo->id, 'Sin Nominar');
    $inscripcion = inscripcionN($this->bekho, $matricula, $this->rojo, $this->dan, $instructor);
    $this->servicio->registrarResultado($inscripcion, ResultadoExamen::Aprobado, 9.6);

    expect($this->servicio->aplicarGraduacion($inscripcion->fresh()))->toBeNull()
        ->and(Graduacion::count())->toBe(0)
        ->and($matricula->persona->fresh()->grado_id)->toBe($this->rojo->id);
});

test('con nominación aprobada sí se gradúa a danes', function () {
    $instructor = User::factory()->create(['grupo_id' => $this->bekho->id]);
    $matricula = matriculaExamenN($this->bekho->id, $this->rojo->id, 'Nominado');
    $this->servicio->resolverNominacion($this->servicio->nominar($matricula->persona, $this->dan), true);

    $inscripcion = inscripcionN($this->bekho, $matricula, $this->rojo, $this->dan, $instructor);
    $this->servicio->registrarResultado($inscripcion, ResultadoExamen::Aprobado, 9.6);

    $graduacion = $this->servicio->aplicarGraduacion($inscripcion->fresh());

    // La graduación se crea, pero el grado sube recién al entregar el cinturón.
    expect($graduacion)->not->toBeNull()
        ->and($matricula->persona->fresh()->grado_id)->toBe($this->rojo->id);

    $this->servicio->registrarEntrega($graduacion);
    expect($matricula->persona->fresh()->grado_id)->toBe($this->dan->id);
});

// --- Entrega -----------------------------------------------------------------

test('el examinador se registra por persona (la del instructor acreditado)', function () {
    $persona = Persona::create(['nombres' => 'Examinador', 'fecha_nacimiento' => now()->subYears(40)]);
    $instructor = User::factory()->create(['grupo_id' => $this->bekho->id, 'persona_id' => $persona->id]);
    $matricula = matriculaExamenN($this->bekho->id, null, 'Alumno');
    $verde = Grado::create(['nombre' => 'Verde', 'orden' => 8, 'escala' => 'adultos', 'color' => 'Verde', 'activo' => true]);

    $inscripcion = inscripcionN($this->bekho, $matricula, $this->rojo, $verde, $instructor);
    $this->servicio->registrarResultado($inscripcion, ResultadoExamen::Aprobado, 9.4);
    $graduacion = $this->servicio->aplicarGraduacion($inscripcion->fresh());

    expect($graduacion->examinador_persona_id)->toBe($persona->id);
});

test('registrar la entrega fija la fecha y cierra el plazo pendiente', function () {
    $instructor = User::factory()->create(['grupo_id' => $this->bekho->id]);
    $verde = Grado::create(['nombre' => 'Verde', 'orden' => 8, 'escala' => 'adultos', 'color' => 'Verde', 'activo' => true]);
    $matricula = matriculaExamenN($this->bekho->id, $this->rojo->id, 'Alumno');
    $inscripcion = inscripcionN($this->bekho, $matricula, $this->rojo, $verde, $instructor);
    $this->servicio->registrarResultado($inscripcion, ResultadoExamen::Aprobado, 9.4);
    $graduacion = $this->servicio->aplicarGraduacion($inscripcion->fresh());

    expect($graduacion->entregaPendiente())->toBeTrue();

    $this->servicio->registrarEntrega($graduacion);

    expect($graduacion->fresh()->entregaPendiente())->toBeFalse()
        ->and($graduacion->fresh()->fecha_entrega)->not->toBeNull();
});

test('una graduación sin entrega vencida a los 30 días aparece en el alerta', function () {
    $g = Graduacion::create([
        'grupo_id' => $this->bekho->id,
        'matricula_id' => matriculaExamenN($this->bekho->id, null, 'Alumno')->id,
        'grado_destino_id' => $this->rojo->id,
        'fecha' => now()->subDays(40),
        'resultado' => ResultadoExamen::Aprobado->value,
    ]);

    expect($g->plazoEntregaVencido())->toBeTrue()
        ->and($this->servicio->entregasVencidas()->pluck('id')->all())->toContain($g->id);
});
