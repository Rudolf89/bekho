<?php

use App\Models\CargoRango;
use App\Models\CreditoGraduacion;
use App\Models\DistintivoRango;
use App\Models\Graduacion;
use App\Models\Grupo;
use App\Models\Instructor;
use App\Models\Matricula;
use App\Models\Persona;
use App\Models\Sede;
use App\Services\ServicioCreditos;
use App\Support\Tenancy\Grupo as Tenant;
use Database\Seeders\CargosRangosSeeder;
use Database\Seeders\DistintivosRangoSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    $this->seed(CargosRangosSeeder::class);
    $this->seed(DistintivosRangoSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->bekho = Grupo::where('nombre', 'BEKHO Power Academy')->first();
    $this->sede = Sede::create(['grupo_id' => $this->bekho->id, 'nombre' => 'Central', 'activo' => true]);
    Tenant::set($this->bekho->id);
    $this->servicio = app(ServicioCreditos::class);
});

afterEach(fn () => Tenant::olvidar());

function personaCred(string $nombre): Persona
{
    return Persona::create(['nombres' => $nombre, 'fecha_nacimiento' => now()->subYears(20)]);
}

/** Da a una persona su faceta de instructor con un supervisor opcional. */
function facetaInstructor(Persona $p, ?Persona $supervisor = null, ?int $rangoId = null): Instructor
{
    return Instructor::create([
        'persona_id' => $p->id,
        'supervisor_persona_id' => $supervisor?->id,
        'rango_id' => $rangoId,
    ]);
}

function matriculaCred(int $grupoId, Persona $persona, ?int $sedeId, ?int $instructorPersonaId): Matricula
{
    return Matricula::withoutGlobalScopes()->create([
        'grupo_id' => $grupoId, 'persona_id' => $persona->id, 'sede_id' => $sedeId,
        'grupo_etario' => 'jovenes_adultos', 'estado' => 'activa', 'fecha_ingreso' => now(),
        'instructor_persona_id' => $instructorPersonaId,
    ]);
}

// --- Reglas de origen --------------------------------------------------------

test('regla 1: el origen es el instructor asignado a la matrícula activa', function () {
    $profe = personaCred('Profe');
    $alumno = personaCred('Alumno');
    matriculaCred($this->bekho->id, $alumno, $this->sede->id, $profe->id);

    expect($this->servicio->resolverOrigen($alumno)?->id)->toBe($profe->id);
});

test('regla 2: sin instructor en la matrícula, el origen es el responsable de la sede', function () {
    $responsable = personaCred('Responsable');
    $this->sede->update(['responsable_persona_id' => $responsable->id]);
    $alumno = personaCred('Alumno');
    matriculaCred($this->bekho->id, $alumno, $this->sede->id, null);

    expect($this->servicio->resolverOrigen($alumno)?->id)->toBe($responsable->id);
});

test('regla 3: sin matrícula, el origen es el supervisor de su faceta de instructor', function () {
    $maestro = personaCred('Maestro');
    $instructor = personaCred('Instructor');
    facetaInstructor($instructor, $maestro);

    // Sin matrícula activa.
    expect($this->servicio->resolverOrigen($instructor)?->id)->toBe($maestro->id);
});

test('regla 4: nunca a sí mismo; si el origen es la propia persona, parte de su profesor', function () {
    $maestro = personaCred('Maestro');
    $profe = personaCred('Profe');
    facetaInstructor($profe, $maestro);
    // El profe es su propio instructor en la matrícula → se salta a su profesor.
    matriculaCred($this->bekho->id, $profe, $this->sede->id, $profe->id);

    expect($this->servicio->resolverOrigen($profe)?->id)->toBe($maestro->id);
});

// --- Cadena ------------------------------------------------------------------

test('la cadena sube por tres o más niveles y otorga un crédito a cada uno', function () {
    $titular = personaCred('Titular');
    $maestro = personaCred('Maestro');
    $profe = personaCred('Profe');
    facetaInstructor($maestro, $titular);
    facetaInstructor($profe, $maestro);

    $alumno = personaCred('Alumno');
    $matricula = matriculaCred($this->bekho->id, $alumno, $this->sede->id, $profe->id);
    $graduacion = Graduacion::create([
        'grupo_id' => $this->bekho->id, 'matricula_id' => $matricula->id, 'fecha' => now(), 'resultado' => 'aprobado',
    ]);

    $this->servicio->otorgar($graduacion);

    expect($graduacion->fresh()->instructor_acreditado_persona_id)->toBe($profe->id)
        ->and(CreditoGraduacion::where('graduacion_id', $graduacion->id)->count())->toBe(3)
        ->and($this->servicio->totalCreditos($profe))->toBe(1)
        ->and($this->servicio->totalCreditos($maestro))->toBe(1)
        ->and($this->servicio->totalCreditos($titular))->toBe(1);

    // Posición 1 = origen.
    expect(CreditoGraduacion::where('graduacion_id', $graduacion->id)->where('persona_id', $profe->id)->value('posicion'))->toBe(1);
});

test('una cadena con ciclo no genera bucle infinito ni créditos duplicados', function () {
    $a = personaCred('A');
    $b = personaCred('B');
    // Ciclo: A supervisa a B y B supervisa a A.
    facetaInstructor($a, $b);
    facetaInstructor($b, $a);

    $cadena = $this->servicio->cadena($a);
    expect($cadena)->toHaveCount(2)
        ->and($cadena->pluck('id')->all())->toBe([$a->id, $b->id]);
});

test('una graduación sin cadena posible no otorga créditos y deja el origen nulo', function () {
    // Alumno sin instructor y sede sin responsable → no hay origen.
    $alumno = personaCred('Solo');
    $matricula = matriculaCred($this->bekho->id, $alumno, $this->sede->id, null);
    $graduacion = Graduacion::create([
        'grupo_id' => $this->bekho->id, 'matricula_id' => $matricula->id, 'fecha' => now(), 'resultado' => 'aprobado',
    ]);

    $this->servicio->otorgar($graduacion);

    expect($graduacion->fresh()->instructor_acreditado_persona_id)->toBeNull()
        ->and(CreditoGraduacion::where('graduacion_id', $graduacion->id)->count())->toBe(0);
});

test('los créditos ya otorgados no cambian al reasignar el profesor después', function () {
    $profe1 = personaCred('Profe 1');
    $profe2 = personaCred('Profe 2');
    $alumno = personaCred('Alumno');
    $matricula = matriculaCred($this->bekho->id, $alumno, $this->sede->id, $profe1->id);
    $graduacion = Graduacion::create([
        'grupo_id' => $this->bekho->id, 'matricula_id' => $matricula->id, 'fecha' => now(), 'resultado' => 'aprobado',
    ]);
    $this->servicio->otorgar($graduacion);

    // Se reasigna el instructor de la matrícula DESPUÉS de otorgar.
    $matricula->update(['instructor_persona_id' => $profe2->id]);

    // El crédito histórico sigue en Profe 1; Profe 2 no gana nada retroactivo.
    expect($this->servicio->totalCreditos($profe1))->toBe(1)
        ->and($this->servicio->totalCreditos($profe2))->toBe(0)
        ->and($graduacion->fresh()->instructor_acreditado_persona_id)->toBe($profe1->id);
});

// --- Distintivo --------------------------------------------------------------

test('el distintivo es nulo mientras los umbrales del rango no estén configurados', function () {
    $profesor = CargoRango::where('nombre', 'Profesor')->first();
    $persona = personaCred('Profesor');
    facetaInstructor($persona, null, $profesor->id);

    // Sembrado con umbrales nulos → sin distintivo aunque tenga créditos.
    CreditoGraduacion::create(['graduacion_id' => Graduacion::create([
        'grupo_id' => $this->bekho->id,
        'matricula_id' => matriculaCred($this->bekho->id, personaCred('X'), $this->sede->id, null)->id,
        'fecha' => now(), 'resultado' => 'aprobado',
    ])->id, 'persona_id' => $persona->id, 'posicion' => 1]);

    expect($this->servicio->distintivoDe($persona))->toBeNull();

    // Al fijar un umbral alcanzable, aparece el distintivo.
    DistintivoRango::where('rango_id', $profesor->id)->where('nombre', 'Negro')->update(['graduados_requeridos' => 1]);
    expect($this->servicio->distintivoDe($persona)?->nombre)->toBe('Negro');
});
