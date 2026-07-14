<?php

use App\Enums\ResultadoExamen;
use App\Models\Academia;
use App\Models\Convocatoria;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Graduacion;
use App\Models\Inscripcion;
use App\Models\User;
use App\Services\ServicioExamenes;
use App\Support\Tenancy\Academia as Tenant;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->bekho = Academia::where('nombre', 'BEKHO Power Academy')->first();
    Tenant::set($this->bekho->id);
});

function usuarioExamen(string $rol, ?int $academiaId, ?int $supervisorId = null): User
{
    $exige2fa = in_array($rol, config('bekho.2fa_obligatorio_para', []), true);
    $user = User::factory()->create([
        'academia_id' => $academiaId,
        'supervisor_id' => $supervisorId,
        'two_factor_confirmed_at' => $exige2fa ? now() : null,
    ]);
    $user->assignRole($rol);

    return $user;
}

// --- Permisos ----------------------------------------------------------------

test('un instructor ve las convocatorias (para inscribir) pero no el conteo de gestión', function () {
    $user = usuarioExamen('instructor', $this->bekho->id);

    Tenant::olvidar();
    // Puede abrir el listado y el detalle porque inscribe.
    $this->actingAs($user)->get(route('examenes.index'))->assertOk();
    // El conteo en cascada (collares) es de gestión: no lo ve.
    $this->actingAs($user)->get(route('examenes.conteo'))->assertForbidden();
});

test('un maestro accede a la gestión de exámenes', function () {
    $user = usuarioExamen('direccion', $this->bekho->id);

    Tenant::olvidar();
    $this->actingAs($user)->get(route('examenes.index'))->assertOk();
});

test('las pantallas de detalle y conteo renderizan para un maestro', function () {
    $user = usuarioExamen('direccion', $this->bekho->id);
    $conv = Convocatoria::create(['academia_id' => $this->bekho->id, 'nombre' => 'Examen', 'fecha' => now(), 'estado' => 'programada']);

    Tenant::olvidar();
    $this->actingAs($user)->get(route('examenes.detalle', $conv))->assertOk();
    $this->actingAs($user)->get(route('examenes.conteo'))->assertOk();
});

// --- Scope por academia ------------------------------------------------------

test('las convocatorias se aíslan por academia', function () {
    $otra = Academia::create(['nombre' => 'OTRA', 'activo' => true]);
    Convocatoria::create(['academia_id' => $this->bekho->id, 'nombre' => 'BEKHO', 'fecha' => now(), 'estado' => 'programada']);
    Convocatoria::create(['academia_id' => $otra->id, 'nombre' => 'OTRA', 'fecha' => now(), 'estado' => 'programada']);

    Tenant::set($this->bekho->id);
    expect(Convocatoria::count())->toBe(1);
    expect(Convocatoria::first()->nombre)->toBe('BEKHO');
});

// --- Regla central: aprobar sube el grado y deja historial -------------------

test('aprobar un examen sube el grado del estudiante y crea el historial', function () {
    $amarillo = Grado::create(['nombre' => 'Amarillo', 'orden' => 2, 'escala' => 'adultos', 'activo' => true]);
    $verde = Grado::create(['nombre' => 'Verde', 'orden' => 3, 'escala' => 'adultos', 'activo' => true]);

    $estudiante = Estudiante::create([
        'academia_id' => $this->bekho->id, 'nombre' => 'Carlos', 'grupo_etario' => 'for_kids',
        'nivel' => 'principiantes', 'grado_id' => $amarillo->id, 'activo' => true,
    ]);
    $conv = Convocatoria::create(['academia_id' => $this->bekho->id, 'nombre' => 'Examen', 'fecha' => now(), 'estado' => 'programada']);
    $instructor = usuarioExamen('instructor', $this->bekho->id);

    $inscripcion = Inscripcion::create([
        'academia_id' => $this->bekho->id, 'convocatoria_id' => $conv->id, 'estudiante_id' => $estudiante->id,
        'grado_origen_id' => $amarillo->id, 'grado_destino_id' => $verde->id, 'instructor_id' => $instructor->id,
        'visto_bueno' => true,
    ]);

    $servicio = app(ServicioExamenes::class);
    $servicio->registrarResultado($inscripcion, ResultadoExamen::Aprobado, 9.3);
    $servicio->finalizar($conv);

    expect($estudiante->fresh()->grado_id)->toBe($verde->id);
    expect(Graduacion::count())->toBe(1);
    // Idempotente: finalizar de nuevo no duplica.
    $servicio->finalizar($conv->fresh());
    expect(Graduacion::count())->toBe(1);
});

test('un examen reprobado no sube el grado ni crea historial', function () {
    $amarillo = Grado::create(['nombre' => 'Amarillo', 'orden' => 2, 'escala' => 'adultos', 'activo' => true]);
    $verde = Grado::create(['nombre' => 'Verde', 'orden' => 3, 'escala' => 'adultos', 'activo' => true]);

    $estudiante = Estudiante::create([
        'academia_id' => $this->bekho->id, 'nombre' => 'Diego', 'grupo_etario' => 'for_kids',
        'nivel' => 'principiantes', 'grado_id' => $amarillo->id, 'activo' => true,
    ]);
    $conv = Convocatoria::create(['academia_id' => $this->bekho->id, 'nombre' => 'Examen', 'fecha' => now(), 'estado' => 'programada']);

    $inscripcion = Inscripcion::create([
        'academia_id' => $this->bekho->id, 'convocatoria_id' => $conv->id, 'estudiante_id' => $estudiante->id,
        'grado_origen_id' => $amarillo->id, 'grado_destino_id' => $verde->id, 'visto_bueno' => true,
    ]);

    $servicio = app(ServicioExamenes::class);
    $servicio->registrarResultado($inscripcion, ResultadoExamen::Reprobado);
    $servicio->finalizar($conv);

    expect($estudiante->fresh()->grado_id)->toBe($amarillo->id);
    expect(Graduacion::count())->toBe(0);
});

// --- Conteo en cascada -------------------------------------------------------

test('el conteo de graduaciones sube por la línea de supervisión', function () {
    $ana = usuarioExamen('direccion', $this->bekho->id);              // jefa
    $beto = usuarioExamen('instructor', $this->bekho->id, $ana->id); // Beto reporta a Ana

    $amarillo = Grado::create(['nombre' => 'Amarillo', 'orden' => 2, 'escala' => 'adultos', 'activo' => true]);
    $verde = Grado::create(['nombre' => 'Verde', 'orden' => 3, 'escala' => 'adultos', 'activo' => true]);

    // Dos graduaciones acreditadas a Beto.
    foreach (['Uno', 'Dos'] as $nombre) {
        $est = Estudiante::create([
            'academia_id' => $this->bekho->id, 'nombre' => $nombre, 'grupo_etario' => 'for_kids',
            'nivel' => 'principiantes', 'grado_id' => $amarillo->id, 'activo' => true,
        ]);
        Graduacion::create([
            'academia_id' => $this->bekho->id, 'estudiante_id' => $est->id, 'grado_origen_id' => $amarillo->id,
            'grado_destino_id' => $verde->id, 'instructor_id' => $beto->id, 'fecha' => now(), 'resultado' => 'aprobado',
        ]);
    }

    $servicio = app(ServicioExamenes::class);

    // Beto: sus 2. Ana: las mismas 2 por cascada (Beto está en su línea).
    expect($servicio->conteoEnCascada($beto))->toBe(2);
    expect($servicio->conteoEnCascada($ana))->toBe(2);
    // Sin umbrales configurados, no hay collar.
    expect($servicio->collarDe($ana))->toBeNull();
});

// --- Elegibilidad ------------------------------------------------------------

test('sin umbrales configurados, todos los activos cumplen elegibilidad', function () {
    $estudiante = Estudiante::create([
        'academia_id' => $this->bekho->id, 'nombre' => 'Eva', 'grupo_etario' => 'for_kids',
        'nivel' => 'principiantes', 'activo' => true,
    ]);

    // Config por defecto: umbrales null → no filtran.
    expect(app(ServicioExamenes::class)->cumpleElegibilidad($estudiante))->toBeTrue();
});

test('con umbral de meses en grado, un alumno recién ingresado no cumple', function () {
    config(['bekho.examenes.meses_minimos_en_grado' => 6]);

    $estudiante = Estudiante::create([
        'academia_id' => $this->bekho->id, 'nombre' => 'Nuevo', 'grupo_etario' => 'for_kids',
        'nivel' => 'principiantes', 'activo' => true,
    ]);

    // Recién creado: 0 meses en grado < 6 → no cumple.
    expect(app(ServicioExamenes::class)->cumpleElegibilidad($estudiante))->toBeFalse();
});
