<?php

use App\Models\Contenido;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\Persona;
use App\Models\User;
use App\Support\Formacion\MigraFormacionAPersona;
use App\Support\Tenancy\Grupo as Tenant;
use Database\Seeders\EtapasProgramaSeeder;
use Database\Seeders\FederacionesSeeder;
use Database\Seeders\GradosSeeder;
use Database\Seeders\LegacySeeder;
use Database\Seeders\ProgramasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(FederacionesSeeder::class);
    $this->seed(ProgramasSeeder::class);
    $this->seed(GradosSeeder::class);
    $this->seed(LegacySeeder::class);        // niveles_legacy + requisitos_legacy (modelo viejo)
    $this->seed(EtapasProgramaSeeder::class); // etapas_programa + requisitos_etapa (modelo nuevo)

    $this->grupo = Grupo::create(['nombre' => 'Academia Test', 'activo' => true]);
});

afterEach(fn () => Tenant::olvidar());

/** Crea una inscripción legacy (modelo viejo) para un user, con horas y un cumplimiento. */
function inscripcionLegacy(int $grupoId, int $userId): int
{
    $nivel1 = DB::table('niveles_legacy')->where('orden', 1)->first();

    $inscId = DB::table('inscripciones_legacy')->insertGetId([
        'grupo_id' => $grupoId, 'user_id' => $userId, 'nivel_legacy_id' => $nivel1->id,
        'estado' => 'en_curso', 'fecha_inicio' => now()->subMonths(2)->toDateString(),
        'created_at' => now(), 'updated_at' => now(),
    ]);

    DB::table('horas_legacy')->insert([
        'inscripcion_legacy_id' => $inscId, 'fecha' => now()->subMonth()->toDateString(),
        'horas' => 40, 'descripcion' => 'Asistencia bloque 1', 'created_at' => now(), 'updated_at' => now(),
    ]);

    return $inscId;
}

test('migra una inscripción legacy a inscripción de programa por persona', function () {
    $persona = Persona::create(['nombres' => 'Trainee', 'fecha_nacimiento' => now()->subYears(15)]);
    $user = User::factory()->create(['grupo_id' => $this->grupo->id, 'persona_id' => $persona->id]);
    inscripcionLegacy($this->grupo->id, $user->id);

    $resumen = (new MigraFormacionAPersona)->ejecutar();

    expect($resumen['inscripciones'])->toBe(1)
        ->and($resumen['horas'])->toBe(1)
        ->and($resumen['huerfanos'])->toBe([]);

    $insc = DB::table('inscripciones_programa')->where('persona_id', $persona->id)->first();
    expect($insc)->not->toBeNull()
        ->and($insc->estado)->toBe('en_curso')
        ->and((int) $insc->etapa_actual_id)->toBeGreaterThan(0);

    // Las horas se conservan (congeladas, origen manual).
    $hora = DB::table('horas_programa')->where('inscripcion_programa_id', $insc->id)->first();
    expect((float) $hora->horas)->toBe(40.0)
        ->and($hora->origen)->toBe('manual');
});

test('una inscripción de un user sin persona queda fuera y se reporta como huérfana', function () {
    $user = User::factory()->create(['grupo_id' => $this->grupo->id, 'persona_id' => null]);
    $inscId = inscripcionLegacy($this->grupo->id, $user->id);

    $resumen = (new MigraFormacionAPersona)->ejecutar();

    expect($resumen['inscripciones'])->toBe(0)
        ->and($resumen['huerfanos'])->toBe([$inscId])
        ->and(DB::table('inscripciones_programa')->count())->toBe(0);
});

test('rellena progreso_contenidos.persona_id desde el user', function () {
    $persona = Persona::create(['nombres' => 'Alumno', 'fecha_nacimiento' => now()->subYears(12)]);
    $user = User::factory()->create(['grupo_id' => $this->grupo->id, 'persona_id' => $persona->id]);

    // Progreso viejo insertado directamente sin persona_id (elude el hook del modelo).
    Tenant::comoSistema(function () use ($user) {
        $nivel = Nivel::create(['grupo_id' => $this->grupo->id, 'nombre' => 'N', 'orden' => 1, 'activo' => true]);
        $contenido = Contenido::create([
            'grupo_id' => $this->grupo->id, 'nivel_id' => $nivel->id, 'titulo' => 'C', 'tipo' => 'texto', 'orden' => 1, 'activo' => true,
        ]);
        DB::table('progreso_contenidos')->insert([
            'grupo_id' => $this->grupo->id, 'user_id' => $user->id, 'contenido_id' => $contenido->id,
            'estado' => 'completado', 'created_at' => now(), 'updated_at' => now(),
        ]);
    });

    $resumen = (new MigraFormacionAPersona)->ejecutar();

    expect($resumen['progreso'])->toBe(1);
    $progreso = DB::table('progreso_contenidos')->where('user_id', $user->id)->first();
    expect((int) $progreso->persona_id)->toBe($persona->id)
        ->and((int) $progreso->registrado_por_user_id)->toBe($user->id);
});

test('es idempotente: correrlo dos veces no duplica', function () {
    $persona = Persona::create(['nombres' => 'Trainee', 'fecha_nacimiento' => now()->subYears(15)]);
    $user = User::factory()->create(['grupo_id' => $this->grupo->id, 'persona_id' => $persona->id]);
    inscripcionLegacy($this->grupo->id, $user->id);

    (new MigraFormacionAPersona)->ejecutar();
    (new MigraFormacionAPersona)->ejecutar();

    expect(DB::table('inscripciones_programa')->count())->toBe(1)
        ->and(DB::table('horas_programa')->count())->toBe(1);
});
