<?php

use App\Enums\ResultadoExamen;
use App\Livewire\Examenes\DetalleConvocatoria;
use App\Models\Convocatoria;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Matricula;
use App\Models\Persona;
use App\Models\User;
use App\Support\Tenancy\Grupo as Tenant;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->bekho = Grupo::where('nombre', 'BEKHO Power Academy')->first();
    Tenant::set($this->bekho->id);

    $this->direccion = User::factory()->create(['grupo_id' => $this->bekho->id, 'two_factor_confirmed_at' => now()]);
    $this->direccion->assignRole('direccion');
    actingAs($this->direccion);

    $this->verde = Grado::create(['nombre' => 'Verde', 'orden' => 3, 'escala' => 'adultos', 'activo' => true]);
});

afterEach(fn () => Tenant::olvidar());

function inscripcionNota(Grupo $grupo, string $tipo): Inscripcion
{
    $persona = Persona::create(['nombres' => 'Alumno '.uniqid(), 'fecha_nacimiento' => now()->subYears(20)]);
    $matricula = Matricula::create([
        'grupo_id' => $grupo->id, 'persona_id' => $persona->id,
        'grupo_etario' => 'jovenes_adultos', 'estado' => 'activa', 'fecha_ingreso' => now(),
    ]);
    $conv = Convocatoria::create(['grupo_id' => $grupo->id, 'nombre' => 'Examen', 'tipo' => $tipo, 'fecha' => now(), 'estado' => 'programada']);

    return Inscripcion::create([
        'grupo_id' => $grupo->id, 'convocatoria_id' => $conv->id, 'matricula_id' => $matricula->id,
        'grado_destino_id' => test()->verde->id, 'visto_bueno' => true,
    ]);
}

function editarNota(Inscripcion $inscripcion, ?float $nota, string $resultado)
{
    return Livewire::test(DetalleConvocatoria::class, ['convocatoria' => $inscripcion->convocatoria])
        ->call('abrirEdicion', $inscripcion->id)
        ->set('ins_resultado', $resultado)
        ->set('ins_nota', $nota)
        ->call('guardarEdicion');
}

test('en convocatoria de instructor la nota fuera de la escala 9.1–9.9 se rechaza', function () {
    $inscripcion = inscripcionNota($this->bekho, 'instructor');

    editarNota($inscripcion, 9.0, ResultadoExamen::Aprobado->value)->assertHasErrors('ins_nota');
    editarNota($inscripcion, 10.0, ResultadoExamen::Aprobado->value)->assertHasErrors('ins_nota');
});

test('aprobar exige la nota mínima de aprobación (9.5)', function () {
    $inscripcion = inscripcionNota($this->bekho, 'instructor');

    // 9.3 está en escala pero no alcanza el mínimo de aprobación.
    editarNota($inscripcion, 9.3, ResultadoExamen::Aprobado->value)->assertHasErrors('ins_nota');
    // 9.6 aprueba sin problemas.
    editarNota($inscripcion, 9.6, ResultadoExamen::Aprobado->value)->assertHasNoErrors();
});

test('un resultado reprobado no exige el mínimo de aprobación', function () {
    $inscripcion = inscripcionNota($this->bekho, 'instructor');

    editarNota($inscripcion, 9.2, ResultadoExamen::Reprobado->value)->assertHasNoErrors();
});

test('en convocatoria de la federación no se valida la escala de la nota', function () {
    $inscripcion = inscripcionNota($this->bekho, 'federacion');

    // Sin nota y aprobado: en tipo federación no se exige la escala ni el mínimo.
    editarNota($inscripcion, null, ResultadoExamen::Aprobado->value)->assertHasNoErrors();
});

test('cambiar el mínimo de aprobación en la configuración cambia la validación', function () {
    config(['bekho.examenes.nota.aprobacion' => 9.1]);
    $inscripcion = inscripcionNota($this->bekho, 'instructor');

    // Con el umbral bajado a 9.1, un 9.3 aprobado ahora pasa.
    editarNota($inscripcion, 9.3, ResultadoExamen::Aprobado->value)->assertHasNoErrors();
});
