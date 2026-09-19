<?php

use App\Models\Grupo;
use App\Models\Instructor;
use App\Models\Persona;
use App\Models\PersonalGrupo;
use App\Models\Tutela;
use App\Models\User;
use App\Support\Tenancy\Grupo as Tenant;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->bekho = Grupo::where('nombre', 'BEKHO Power Academy')->first();
});

afterEach(fn () => Tenant::olvidar());

// --- users ↔ persona ---------------------------------------------------------

test('un usuario cuelga de una persona (identidad)', function () {
    $persona = Persona::create(['nombres' => 'Adulta', 'fecha_nacimiento' => now()->subYears(30)]);
    $user = User::factory()->create(['persona_id' => $persona->id]);

    expect($user->persona->id)->toBe($persona->id)
        ->and($persona->user->id)->toBe($user->id);
});

// --- tutelas -----------------------------------------------------------------

test('una tutela vincula apoderado y alumno y es única por par', function () {
    $apoderado = Persona::create(['nombres' => 'Madre', 'fecha_nacimiento' => now()->subYears(40)]);
    $hijo = Persona::create(['nombres' => 'Hijo', 'fecha_nacimiento' => now()->subYears(8)]);

    Tutela::create([
        'apoderado_persona_id' => $apoderado->id, 'alumno_persona_id' => $hijo->id,
        'parentesco' => 'madre', 'responsable_pago' => true,
    ]);

    expect(fn () => Tutela::create([
        'apoderado_persona_id' => $apoderado->id, 'alumno_persona_id' => $hijo->id, 'parentesco' => 'madre',
    ]))->toThrow(QueryException::class);
});

test('la tutela puede cruzar grupos (no lleva grupo_id)', function () {
    expect(Schema::hasColumn('tutelas', 'grupo_id'))->toBeFalse();
});

test('la vigencia de la tutela respeta las fechas desde/hasta', function () {
    $apoderado = Persona::create(['nombres' => 'Padre', 'fecha_nacimiento' => now()->subYears(45)]);
    $hijo = Persona::create(['nombres' => 'Hija', 'fecha_nacimiento' => now()->subYears(7)]);

    $vigente = Tutela::create([
        'apoderado_persona_id' => $apoderado->id, 'alumno_persona_id' => $hijo->id, 'parentesco' => 'padre',
        'vigente_desde' => now()->subYear(), 'vigente_hasta' => now()->addYear(),
    ]);
    $vencida = Tutela::create([
        'apoderado_persona_id' => $apoderado->id, 'alumno_persona_id' => Persona::create(['nombres' => 'Otro', 'fecha_nacimiento' => now()->subYears(9)])->id,
        'parentesco' => 'padre', 'vigente_hasta' => now()->subMonth(),
    ]);

    expect($vigente->estaVigente())->toBeTrue()
        ->and($vencida->estaVigente())->toBeFalse();
});

// --- instructores ------------------------------------------------------------

test('el instructor es una faceta transversal de la persona con árbol de supervisión', function () {
    expect(Schema::hasColumn('instructores', 'grupo_id'))->toBeFalse();

    $maestro = Persona::create(['nombres' => 'Maestro', 'fecha_nacimiento' => now()->subYears(50)]);
    $pupilo = Persona::create(['nombres' => 'Pupilo', 'fecha_nacimiento' => now()->subYears(25)]);

    $insMaestro = Instructor::create(['persona_id' => $maestro->id, 'fecha_certificacion' => now()->subYears(10)]);
    $insPupilo = Instructor::create(['persona_id' => $pupilo->id, 'supervisor_persona_id' => $maestro->id]);

    expect($insPupilo->supervisor->id)->toBe($maestro->id)
        ->and($insMaestro->supervisados()->count())->toBe(1)
        ->and($insMaestro->supervisados()->first()->id)->toBe($insPupilo->id);
});

test('cada persona tiene a lo más un registro de instructor', function () {
    $p = Persona::create(['nombres' => 'Único', 'fecha_nacimiento' => now()->subYears(30)]);
    Instructor::create(['persona_id' => $p->id]);

    expect(fn () => Instructor::create(['persona_id' => $p->id]))->toThrow(QueryException::class);
});

// --- personal_grupo ----------------------------------------------------------

test('el personal se aísla por grupo y es único por persona y grupo', function () {
    $otra = Grupo::create(['nombre' => 'Otro Grupo', 'activo' => true]);
    $p1 = Persona::create(['nombres' => 'Recep', 'fecha_nacimiento' => now()->subYears(30)]);
    $p2 = Persona::create(['nombres' => 'Dir', 'fecha_nacimiento' => now()->subYears(35)]);

    PersonalGrupo::create(['persona_id' => $p1->id, 'grupo_id' => $this->bekho->id, 'activo' => true]);
    PersonalGrupo::create(['persona_id' => $p2->id, 'grupo_id' => $otra->id, 'activo' => true]);

    Tenant::set($this->bekho->id);
    expect(PersonalGrupo::count())->toBe(1)
        ->and(PersonalGrupo::first()->persona_id)->toBe($p1->id);

    // Único por persona + grupo.
    expect(fn () => PersonalGrupo::withoutGlobalScopes()->create([
        'persona_id' => $p1->id, 'grupo_id' => $this->bekho->id,
    ]))->toThrow(QueryException::class);
});

test('el personal autorrellena el grupo activo al crear', function () {
    Tenant::set($this->bekho->id);
    $p = Persona::create(['nombres' => 'Nuevo', 'fecha_nacimiento' => now()->subYears(28)]);

    $personal = PersonalGrupo::create(['persona_id' => $p->id, 'activo' => true]);

    expect($personal->grupo_id)->toBe($this->bekho->id);
});
