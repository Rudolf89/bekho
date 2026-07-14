<?php

use App\Enums\Genero;
use App\Enums\NivelEntrenamiento;
use App\Livewire\Inscripcion\InscribirAlumno;
use App\Models\Academia;
use App\Models\Estudiante;
use App\Models\Sede;
use App\Models\User;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    foreach (['super-admin', 'maestro', 'instructor'] as $rol) {
        Role::findOrCreate($rol, 'web');
    }

    $this->academia = Academia::create(['nombre' => 'ATA', 'activo' => true]);
    $this->sede = Sede::create(['academia_id' => $this->academia->id, 'nombre' => 'Neptuno', 'activo' => true]);

    $this->instructor = User::factory()->create(['academia_id' => $this->academia->id]);
    $this->instructor->assignRole('instructor');

    $usuario = User::factory()->create(['academia_id' => $this->academia->id]);
    $usuario->assignRole('maestro');
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

test('inscribir crea la ficha del alumno con los datos del formulario', function () {
    inscribir()->call('inscribir')->assertHasNoErrors();

    $est = Estudiante::sinAcademia()->where('rut', '12345678-9')->first();

    expect($est)->not->toBeNull()
        ->and($est->nombre)->toBe('Juan Andrés Pérez Soto')
        ->and($est->academia_id)->toBe($this->academia->id)
        ->and($est->genero)->toBe(Genero::Masculino)
        ->and($est->comuna)->toBe('Ñuñoa')
        ->and($est->region)->toBe('Metropolitana de Santiago')
        ->and($est->dia_vencimiento)->toBe(5)
        ->and($est->instructor_id)->toBe($this->instructor->id)
        ->and($est->acepto_reglamento)->toBeTrue()
        ->and($est->acepto_reglamento_at)->not->toBeNull()
        ->and($est->activo)->toBeTrue()
        // Alumno nuevo sin cinturón => Principiantes.
        ->and($est->nivel)->toBe(NivelEntrenamiento::Principiantes);
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

test('el apoderado 1 no es obligatorio para Jóvenes y Adultos', function () {
    inscribir()
        ->set('fecha_nacimiento', now()->subYears(25)->format('Y-m-d'))
        ->set('grupo_etario', 'jovenes_adultos')
        ->set('apoderado_1', '')
        ->call('inscribir')
        ->assertHasNoErrors();
});

test('la comuna debe pertenecer a la región elegida', function () {
    inscribir()->set('region', 'Valparaíso')->set('comuna', 'Ñuñoa')->call('inscribir')
        ->assertHasErrors('comuna');
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
