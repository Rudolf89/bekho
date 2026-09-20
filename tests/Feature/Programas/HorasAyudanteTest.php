<?php

use App\Enums\EstadoLegacy;
use App\Models\AsistenciaAyudante;
use App\Models\Clase;
use App\Models\Grupo;
use App\Models\HoraPrograma;
use App\Models\InscripcionPrograma;
use App\Models\Persona;
use App\Models\Programa;
use App\Models\Sede;
use App\Services\ServicioHorasAyudante;
use App\Support\Tenancy\Grupo as Tenant;
use Carbon\Carbon;
use Database\Seeders\EtapasProgramaSeeder;
use Database\Seeders\FederacionesSeeder;
use Database\Seeders\GradosSeeder;
use Database\Seeders\ProgramasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(FederacionesSeeder::class);
    $this->seed(ProgramasSeeder::class);
    $this->seed(GradosSeeder::class);
    $this->seed(EtapasProgramaSeeder::class);

    $this->grupo = Grupo::create(['nombre' => 'Academia Test', 'activo' => true]);
    Tenant::set($this->grupo->id);
    $this->sede = Sede::create(['grupo_id' => $this->grupo->id, 'nombre' => 'Central', 'activo' => true]);
    $this->legacy = Programa::where('nombre', 'Legacy')->first();
    $this->servicio = app(ServicioHorasAyudante::class);
});

afterEach(fn () => Tenant::olvidar());

/** Clase con un horario los lunes 18:00–19:30 (1,5 h). */
function claseConHorarioLunes(int $grupoId, int $sedeId): Clase
{
    $clase = Clase::create([
        'grupo_id' => $grupoId, 'sede_id' => $sedeId, 'nombre' => 'Kids A',
        'grupo_etario' => 'for_kids', 'activo' => true,
    ]);
    $clase->horarios()->create(['dia_semana' => 1, 'hora_inicio' => '18:00', 'hora_fin' => '19:30']);

    return $clase;
}

function traineeConInscripcionLegacy(Programa $legacy): Persona
{
    $persona = Persona::create(['nombres' => 'Trainee', 'fecha_nacimiento' => now()->subYears(16)]);
    InscripcionPrograma::create([
        'persona_id' => $persona->id, 'programa_id' => $legacy->id,
        'etapa_actual_id' => $legacy->etapas()->orderBy('orden')->first()->id,
        'estado' => EstadoLegacy::EnCurso->value, 'fecha_ingreso' => now(),
    ]);

    return $persona;
}

test('horasDeClaseEnFecha suma la duración de los horarios del día', function () {
    $clase = claseConHorarioLunes($this->grupo->id, $this->sede->id);

    // 2026-01-05 es lunes; 2026-01-06 es martes.
    expect($this->servicio->horasDeClaseEnFecha($clase, Carbon::parse('2026-01-05')))->toBe(1.5)
        ->and($this->servicio->horasDeClaseEnFecha($clase, Carbon::parse('2026-01-06')))->toBe(0.0);
});

test('marcar acredita las horas congeladas a la inscripción que las exige', function () {
    $clase = claseConHorarioLunes($this->grupo->id, $this->sede->id);
    $persona = traineeConInscripcionLegacy($this->legacy);

    $marca = $this->servicio->marcar($persona, $clase, Carbon::parse('2026-01-05'));

    expect((float) $marca->horas)->toBe(1.5);

    $inscripcion = InscripcionPrograma::where('persona_id', $persona->id)->first();
    $hora = $inscripcion->horas()->first();
    expect((float) $inscripcion->horasAcumuladas())->toBe(1.5)
        ->and($hora->origen)->toBe('asistencia')
        ->and($hora->asistencia_ayudante_id)->toBe($marca->id);
});

test('marcar es idempotente: no duplica horas al re-marcar la misma sesión', function () {
    $clase = claseConHorarioLunes($this->grupo->id, $this->sede->id);
    $persona = traineeConInscripcionLegacy($this->legacy);

    $this->servicio->marcar($persona, $clase, Carbon::parse('2026-01-05'));
    $this->servicio->marcar($persona, $clase, Carbon::parse('2026-01-05'));

    expect(AsistenciaAyudante::count())->toBe(1)
        ->and(HoraPrograma::where('origen', 'asistencia')->count())->toBe(1);
});

test('sin inscripción que exija horas, la marca se registra pero no acredita horas', function () {
    $clase = claseConHorarioLunes($this->grupo->id, $this->sede->id);
    $persona = Persona::create(['nombres' => 'Sin inscripción', 'fecha_nacimiento' => now()->subYears(16)]);

    $marca = $this->servicio->marcar($persona, $clase, Carbon::parse('2026-01-05'));

    expect($marca->exists)->toBeTrue()
        ->and(HoraPrograma::count())->toBe(0);
});

test('quitar la marca elimina también su hora acreditada', function () {
    $clase = claseConHorarioLunes($this->grupo->id, $this->sede->id);
    $persona = traineeConInscripcionLegacy($this->legacy);

    $marca = $this->servicio->marcar($persona, $clase, Carbon::parse('2026-01-05'));
    expect(HoraPrograma::where('origen', 'asistencia')->count())->toBe(1);

    $this->servicio->quitar($marca);

    expect(AsistenciaAyudante::count())->toBe(0)
        ->and(HoraPrograma::where('origen', 'asistencia')->count())->toBe(0);
});
