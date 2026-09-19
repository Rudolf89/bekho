<?php

use App\Enums\Genero;
use App\Enums\GrupoEtario;
use App\Enums\TipoDocumento;
use App\Livewire\Inscripcion\InscribirAlumno;
use App\Models\DocumentoPersona;
use App\Models\Grupo;
use App\Models\Sede;
use App\Models\User;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    foreach (['admin-plataforma', 'direccion', 'instructor'] as $rol) {
        Role::findOrCreate($rol, 'web');
    }

    $this->grupo = Grupo::create(['nombre' => 'ATA', 'activo' => true]);
    $this->sede = Sede::create(['grupo_id' => $this->grupo->id, 'nombre' => 'Neptuno', 'activo' => true]);

    $this->instructor = User::factory()->create(['grupo_id' => $this->grupo->id]);
    $this->instructor->assignRole('instructor');
    // El instructor debe estar asignado a la sede para poder elegirlo en la ficha.
    $this->instructor->sedes()->attach($this->sede->id);

    $usuario = User::factory()->create(['grupo_id' => $this->grupo->id]);
    $usuario->assignRole('direccion');
    actingAs($usuario);
});

function inscribir(): Testable
{
    return Livewire::test(InscribirAlumno::class)
        ->set('sede_id', (string) test()->sede->id)
        ->set('instructor_id', (string) test()->instructor->id)
        ->set('nombres', 'Juan Andrés')
        ->set('apellido_paterno', 'Pérez')
        ->set('apellido_materno', 'Soto')
        ->set('rut', '12345678-9')
        ->set('fecha_nacimiento', now()->subYears(9)->format('Y-m-d'))
        ->set('genero', 'masculino')
        ->set('grupo_etario', 'for_kids')
        ->set('apoderado_1', 'María Pérez')
        ->set('direccion', 'Av. Siempre Viva 123')
        ->set('region', 'Metropolitana de Santiago')
        ->set('comuna', 'Ñuñoa')
        ->set('telefono_contacto', '987654321')
        ->set('email_contacto', 'contacto@ejemplo.cl')
        ->set('dia_vencimiento', '5')
        ->set('acepto_reglamento', true);
}

test('inscribir crea la persona, su documento y la matrícula', function () {
    inscribir()->call('inscribir')->assertHasNoErrors();

    $documento = DocumentoPersona::where('numero', '12345678-9')->first();
    expect($documento)->not->toBeNull()->and($documento->tipo)->toBe(TipoDocumento::Rut);

    $persona = $documento->persona;
    expect($persona->nombreCompleto())->toBe('Juan Andrés Pérez Soto')
        ->and($persona->genero)->toBe(Genero::Masculino)
        ->and($persona->comuna)->toBe('Ñuñoa')
        ->and($persona->region)->toBe('Metropolitana de Santiago')
        // El apoderado queda como contacto de emergencia de la persona.
        ->and($persona->contacto_emergencia_nombre)->toBe('María Pérez');

    $matricula = $persona->matriculaActiva;
    expect($matricula)->not->toBeNull()
        ->and($matricula->grupo_id)->toBe($this->grupo->id)
        ->and($matricula->grupo_etario)->toBe(GrupoEtario::ForKids)
        ->and($matricula->dia_vencimiento)->toBe(5)
        ->and($matricula->acepto_reglamento_at)->not->toBeNull();
});

test('el reglamento debe aceptarse', function () {
    inscribir()->set('acepto_reglamento', false)->call('inscribir')->assertHasErrors('acepto_reglamento');
});

test('el apoderado 1 es obligatorio para Tigers y For Kids', function () {
    inscribir()
        ->set('grupo_etario', 'for_kids')
        ->set('apoderado_1', '')
        ->call('inscribir')
        ->assertHasErrors('apoderado_1');

    inscribir()
        ->set('grupo_etario', 'tigers')
        ->set('apoderado_1', '')
        ->call('inscribir')
        ->assertHasErrors('apoderado_1');
});

test('el apoderado 1 queda marcado como obligatorio mientras no se elija Jóvenes y Adultos', function () {
    // Por defecto (sin grupo elegido aún) el apoderado se exige, para que el
    // campo no quede sin marcar frente al teléfono/correo (siempre obligatorios).
    $comp = Livewire::test(InscribirAlumno::class);
    expect($comp->instance()->requiereApoderado())->toBeTrue();

    $comp->set('grupo_etario', 'jovenes_adultos');
    expect($comp->instance()->requiereApoderado())->toBeFalse();
});

test('el apoderado 1 no es obligatorio para Jóvenes y Adultos', function () {
    inscribir()
        ->set('fecha_nacimiento', now()->subYears(25)->format('Y-m-d'))
        ->set('grupo_etario', 'jovenes_adultos')
        ->set('apoderado_1', '')
        ->call('inscribir')
        ->assertHasNoErrors();
});

test('el día de vencimiento solo admite 1, 5, 10 o 15', function () {
    inscribir()->set('dia_vencimiento', '20')->call('inscribir')->assertHasErrors('dia_vencimiento');
    inscribir()->set('dia_vencimiento', '10')->call('inscribir')->assertHasNoErrors();
});

test('la comuna debe pertenecer a la región elegida', function () {
    inscribir()->set('region', 'Valparaíso')->set('comuna', 'Ñuñoa')->call('inscribir')
        ->assertHasErrors('comuna');
});

test('los instructores disponibles son solo los de la sede elegida', function () {
    $otraSede = Sede::create(['grupo_id' => $this->grupo->id, 'nombre' => 'Marte', 'activo' => true]);
    $instructorOtraSede = User::factory()->create(['grupo_id' => $this->grupo->id]);
    $instructorOtraSede->assignRole('instructor');
    $instructorOtraSede->sedes()->attach($otraSede->id);

    Livewire::test(InscribirAlumno::class)
        ->set('sede_id', (string) $this->sede->id)
        ->assertViewHas('instructores', fn ($ins) => $ins->contains($this->instructor)
            && ! $ins->contains($instructorOtraSede));
});

test('al cambiar de sede se limpia el instructor elegido', function () {
    $otraSede = Sede::create(['grupo_id' => $this->grupo->id, 'nombre' => 'Marte', 'activo' => true]);

    Livewire::test(InscribirAlumno::class)
        ->set('sede_id', (string) $this->sede->id)
        ->set('instructor_id', (string) $this->instructor->id)
        ->set('sede_id', (string) $otraSede->id)
        ->assertSet('instructor_id', '');
});

test('no se puede inscribir con un instructor que no pertenece a la sede', function () {
    $otraSede = Sede::create(['grupo_id' => $this->grupo->id, 'nombre' => 'Marte', 'activo' => true]);
    $ajeno = User::factory()->create(['grupo_id' => $this->grupo->id]);
    $ajeno->assignRole('instructor');
    $ajeno->sedes()->attach($otraSede->id);

    inscribir()->set('instructor_id', (string) $ajeno->id)->call('inscribir')
        ->assertHasErrors('instructor_id');
});

test('la fecha de nacimiento sugiere el grupo etario', function () {
    Livewire::test(InscribirAlumno::class)
        ->set('fecha_nacimiento', now()->subYears(5)->format('Y-m-d'))
        ->assertSet('grupo_etario', 'tigers')
        ->set('fecha_nacimiento', now()->subYears(9)->format('Y-m-d'))
        ->assertSet('grupo_etario', 'for_kids')
        ->set('fecha_nacimiento', now()->subYears(20)->format('Y-m-d'))
        ->assertSet('grupo_etario', 'jovenes_adultos');
});

test('la fecha de nacimiento calcula la edad del alumno', function () {
    $comp = Livewire::test(InscribirAlumno::class)
        ->set('fecha_nacimiento', now()->subYears(9)->subMonths(3)->format('Y-m-d'));

    expect($comp->instance()->edad())->toBe(9);
});

test('sugiere pasar al grupo siguiente si está por cumplir la edad', function () {
    // 6 años, cumple 7 en ~2 meses → puede pasar de Tigers a For Kids.
    $comp = Livewire::test(InscribirAlumno::class)
        ->set('fecha_nacimiento', now()->subYears(7)->addMonths(2)->format('Y-m-d'))
        ->assertSet('grupo_etario', 'tigers');

    $sugerencia = $comp->instance()->sugerenciaProximoGrupo();

    expect($comp->instance()->edad())->toBe(6)
        ->and($sugerencia)->not->toBeNull()
        ->and($sugerencia['grupo'])->toBe(GrupoEtario::ForKids)
        ->and($sugerencia['edadProxima'])->toBe(7);

    // El botón de la sugerencia cambia el grupo elegido.
    $comp->call('cambiarGrupo', 'for_kids')->assertSet('grupo_etario', 'for_kids');
});

test('desmarcar el segundo apoderado limpia sus datos', function () {
    Livewire::test(InscribirAlumno::class)
        ->set('agregarApoderado2', true)
        ->set('apoderado_2', 'María Soto')
        ->set('telefono_contacto_2', '987654321')
        ->set('email_contacto_2', 'maria@ejemplo.cl')
        ->set('agregarApoderado2', false)
        ->assertSet('apoderado_2', null)
        ->assertSet('telefono_contacto_2', null)
        ->assertSet('email_contacto_2', null);
});

test('no sugiere cambio de grupo si el cumpleaños está lejos', function () {
    // 6 años, cumple 7 recién en ~8 meses → todavía no se sugiere el cambio.
    $comp = Livewire::test(InscribirAlumno::class)
        ->set('fecha_nacimiento', now()->subYears(7)->addMonths(8)->format('Y-m-d'));

    expect($comp->instance()->edad())->toBe(6)
        ->and($comp->instance()->sugerenciaProximoGrupo())->toBeNull();
});
