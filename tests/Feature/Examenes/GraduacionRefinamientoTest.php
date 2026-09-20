<?php

use App\Enums\ResultadoExamen;
use App\Models\Convocatoria;
use App\Models\Grado;
use App\Models\Graduacion;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Matricula;
use App\Models\Persona;
use App\Models\Sede;
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
    $this->sede = Sede::create(['grupo_id' => $this->bekho->id, 'nombre' => 'Central', 'activo' => true]);
    Tenant::set($this->bekho->id);
    $this->servicio = app(ServicioExamenes::class);
    $this->amarillo = Grado::create(['nombre' => 'Amarillo', 'orden' => 2, 'escala' => 'adultos', 'activo' => true]);
    $this->verde = Grado::create(['nombre' => 'Verde', 'orden' => 3, 'escala' => 'adultos', 'activo' => true]);
});

afterEach(fn () => Tenant::olvidar());

function matriculaRef(int $grupoId, int $sedeId, ?int $gradoId, ?int $instructorPersonaId = null): Matricula
{
    $persona = Persona::create(['nombres' => 'Alumno '.uniqid(), 'fecha_nacimiento' => now()->subYears(20), 'grado_id' => $gradoId]);

    return Matricula::create([
        'grupo_id' => $grupoId, 'persona_id' => $persona->id, 'sede_id' => $sedeId,
        'grupo_etario' => 'jovenes_adultos', 'estado' => 'activa', 'fecha_ingreso' => now(),
        'instructor_persona_id' => $instructorPersonaId,
    ]);
}

test('un alumno reprobado puede volver a rendir en la misma convocatoria', function () {
    $matricula = matriculaRef($this->bekho->id, $this->sede->id, $this->amarillo->id);
    $conv = Convocatoria::create(['grupo_id' => $this->bekho->id, 'nombre' => 'Examen', 'fecha' => now(), 'estado' => 'programada']);

    // Primer intento: reprobado.
    $primero = Inscripcion::create([
        'grupo_id' => $this->bekho->id, 'convocatoria_id' => $conv->id, 'matricula_id' => $matricula->id,
        'grado_origen_id' => $this->amarillo->id, 'grado_destino_id' => $this->verde->id,
        'visto_bueno' => true, 'resultado' => ResultadoExamen::Reprobado->value,
    ]);

    // Segundo intento en la MISMA convocatoria: ya no lo impide un índice único.
    $segundo = Inscripcion::create([
        'grupo_id' => $this->bekho->id, 'convocatoria_id' => $conv->id, 'matricula_id' => $matricula->id,
        'grado_origen_id' => $this->amarillo->id, 'grado_destino_id' => $this->verde->id,
        'visto_bueno' => true, 'resultado' => ResultadoExamen::Aprobado->value, 'nota' => 9.6,
    ]);

    expect(Inscripcion::where('convocatoria_id', $conv->id)->where('matricula_id', $matricula->id)->count())->toBe(2);

    $this->servicio->finalizar($conv);

    // Solo el intento aprobado gradúa; una sola graduación por (convocatoria, matrícula).
    expect(Graduacion::count())->toBe(1);
});

test('el crédito va al instructor del alumno, no al examinador', function () {
    // Instructor acreditado (persona en la matrícula) distinto del examinador.
    $profe = Persona::create(['nombres' => 'Profe', 'fecha_nacimiento' => now()->subYears(35)]);
    $examinadorPersona = Persona::create(['nombres' => 'Examinador', 'fecha_nacimiento' => now()->subYears(45)]);
    $examinador = User::factory()->create(['grupo_id' => $this->bekho->id, 'persona_id' => $examinadorPersona->id]);

    $matricula = matriculaRef($this->bekho->id, $this->sede->id, $this->amarillo->id, $profe->id);
    $conv = Convocatoria::create(['grupo_id' => $this->bekho->id, 'nombre' => 'Examen', 'fecha' => now(), 'estado' => 'programada']);
    $inscripcion = Inscripcion::create([
        'grupo_id' => $this->bekho->id, 'convocatoria_id' => $conv->id, 'matricula_id' => $matricula->id,
        'grado_origen_id' => $this->amarillo->id, 'grado_destino_id' => $this->verde->id,
        'instructor_id' => $examinador->id, 'visto_bueno' => true, 'resultado' => ResultadoExamen::Aprobado->value, 'nota' => 9.6,
    ]);

    $graduacion = $this->servicio->aplicarGraduacion($inscripcion->fresh());

    expect($graduacion->instructor_acreditado_persona_id)->toBe($profe->id)
        ->and($graduacion->examinador_persona_id)->toBe($examinadorPersona->id)
        ->and($graduacion->instructor_acreditado_persona_id)->not->toBe($graduacion->examinador_persona_id);
});
