<?php

use App\Livewire\Inscripcion\InscribirAlumno;
use App\Models\Asistencia;
use App\Models\Cargo;
use App\Models\Clase;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Persona;
use App\Models\Sede;
use App\Models\TipoCargo;
use App\Services\ServicioCargos;
use App\Services\ServicioPagos;
use App\Support\Tenancy\Grupo as Tenant;
use Database\Seeders\CatalogosFederacionSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    $this->seed(CatalogosFederacionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->bekho = Grupo::where('nombre', 'BEKHO Power Academy')->first();
    $this->sede = Sede::create(['grupo_id' => $this->bekho->id, 'nombre' => 'Central', 'activo' => true]);
    Tenant::set($this->bekho->id);
});

afterEach(fn () => Tenant::olvidar());

function matriculaReg(int $grupoId, int $sedeId, string $ingreso = 'now'): Matricula
{
    $persona = Persona::create(['nombres' => 'Alumno '.uniqid(), 'fecha_nacimiento' => now()->subYears(12)]);

    return Matricula::create([
        'grupo_id' => $grupoId, 'persona_id' => $persona->id, 'sede_id' => $sedeId,
        'grupo_etario' => 'for_kids', 'estado' => 'activa',
        'fecha_ingreso' => $ingreso === 'now' ? now() : $ingreso,
    ]);
}

// --- Clases de gracia --------------------------------------------------------

test('las clases de gracia se leen de la sede (con respaldo de la federación)', function () {
    $mensualidad = TipoCargo::where('recurrente', true)->orderBy('orden')->first();
    $matricula = matriculaReg($this->bekho->id, $this->sede->id, now()->subMonths(3)->toDateString());
    Cargo::create([
        'grupo_id' => $this->bekho->id, 'matricula_id' => $matricula->id, 'sede_id' => $this->sede->id,
        'tipo_cargo_id' => $mensualidad->id, 'periodo' => now()->subMonth()->startOfMonth(),
        'monto' => 30000, 'vence_el' => now()->subDays(40), 'estado' => 'pendiente',
    ]);
    $clase = Clase::create(['grupo_id' => $this->bekho->id, 'sede_id' => $this->sede->id, 'nombre' => 'Kids', 'grupo_etario' => 'for_kids', 'activo' => true]);
    // Una sola clase presente tras el vencimiento.
    Asistencia::create(['grupo_id' => $this->bekho->id, 'clase_id' => $clase->id, 'matricula_id' => $matricula->id, 'fecha' => now()->subDays(10), 'estado' => 'presente']);

    $pagos = app(ServicioPagos::class);

    // Por defecto (3 clases de gracia) una sola clase no bloquea.
    expect($pagos->estaBloqueadoPorDeuda($matricula))->toBeFalse();

    // Si la sede baja la gracia a 1, esa misma clase ya bloquea.
    $this->sede->update(['clases_gracia_morosidad' => 1]);
    expect($pagos->clasesGracia($matricula->fresh()))->toBe(1)
        ->and($pagos->estaBloqueadoPorDeuda($matricula->fresh()))->toBeTrue();
});

// --- Exención de matrícula ---------------------------------------------------

test('la ventana de exención de matrícula se lee de la sede', function () {
    // Ingreso en septiembre de 2025.
    $matricula = matriculaReg($this->bekho->id, $this->sede->id, '2025-09-15');
    $servicio = app(ServicioCargos::class);

    // Ventana por defecto (octubre → enero): septiembre queda fuera → no exenta.
    expect($servicio->exentaDeMatricula($matricula, 2026))->toBeFalse();

    // La sede amplía la ventana desde septiembre → ahora sí queda exenta.
    $this->sede->update(['exencion_matricula_desde_mes' => 9]);
    expect($servicio->exentaDeMatricula($matricula->fresh(), 2026))->toBeTrue();
});

// --- Día máximo de vencimiento ----------------------------------------------

test('el día máximo de vencimiento se lee de la sede', function () {
    $comp = Livewire::test(InscribirAlumno::class)->set('sede_id', (string) $this->sede->id);
    // Por defecto llega hasta el 20.
    expect($comp->instance()->diasVencimiento())->toBe([5, 10, 15, 20]);

    // Si la sede sube el máximo a 25, se ofrece un día más.
    $this->sede->update(['dia_vencimiento_maximo' => 25]);
    $comp = Livewire::test(InscribirAlumno::class)->set('sede_id', (string) $this->sede->id);
    expect($comp->instance()->diasVencimiento())->toBe([5, 10, 15, 20, 25]);
});

// --- Tipo de cargo por código estable ----------------------------------------

test('el tipo Matrícula se ubica por código aunque se renombre', function () {
    $servicio = app(ServicioCargos::class);
    expect($servicio->tipoMatricula()?->codigo)->toBe('matricula');

    // La federación renombra el tipo; el código estable lo sigue identificando.
    TipoCargo::where('codigo', 'matricula')->update(['nombre' => 'Inscripción anual']);
    expect($servicio->tipoMatricula()?->nombre)->toBe('Inscripción anual');
});
