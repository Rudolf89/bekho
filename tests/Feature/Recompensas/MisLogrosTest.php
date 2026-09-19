<?php

use App\Enums\GrupoEtario;
use App\Enums\TipoRecompensa;
use App\Livewire\Recompensas\MisLogros;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Persona;
use App\Models\Recompensa;
use App\Models\Tutela;
use App\Models\User;
use App\Support\Tenancy\Grupo as Tenant;
use Database\Seeders\RecompensasSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    $this->seed(RecompensasSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->bekho = Grupo::where('nombre', 'BEKHO Power Academy')->first();
    Tenant::set($this->bekho->id);
});

afterEach(fn () => Tenant::olvidar());

/**
 * Persona (alumno) con matrícula activa y un logro del tipo dado. El logro se
 * otorga a la matrícula; la persona es la identidad transversal.
 */
function personaConLogro(Grupo $a, TipoRecompensa $tipo): Persona
{
    $persona = Persona::create([
        'nombres' => 'Hijo '.uniqid(),
        'fecha_nacimiento' => now()->subYears(9),
    ]);

    $matricula = Matricula::create([
        'grupo_id' => $a->id, 'persona_id' => $persona->id,
        'grupo_etario' => GrupoEtario::ForKids->value, 'estado' => 'activa', 'fecha_ingreso' => now(),
    ]);

    $recompensa = Recompensa::where('tipo', $tipo)->first();
    $matricula->logros()->create([
        'recompensa_id' => $recompensa->id, 'otorgado_at' => now(), 'grupo_id' => $a->id,
    ]);

    return $persona;
}

// --- Apoderado ---------------------------------------------------------------

test('el apoderado ve la colección de logros de sus hijos', function () {
    $apoderadoPersona = Persona::create(['nombres' => 'Apoderado', 'fecha_nacimiento' => now()->subYears(40)]);
    $apoderado = User::factory()->create(['grupo_id' => $this->bekho->id, 'persona_id' => $apoderadoPersona->id]);
    $apoderado->assignRole('apoderado');

    $hijo = personaConLogro($this->bekho, TipoRecompensa::Coleccionable);
    Tutela::create([
        'apoderado_persona_id' => $apoderadoPersona->id,
        'alumno_persona_id' => $hijo->id,
        'parentesco' => 'padre',
    ]);

    // Hijo de OTRO apoderado (no debe verse).
    $ajeno = personaConLogro($this->bekho, TipoRecompensa::Coleccionable);

    Livewire::actingAs($apoderado)->test(MisLogros::class)
        ->assertSee($hijo->nombres)
        ->assertDontSee($ajeno->nombres)
        ->assertSee('Coleccionables');
});

// --- Alumno ------------------------------------------------------------------

test('el alumno ve su propia colección', function () {
    $persona = personaConLogro($this->bekho, TipoRecompensa::Coleccionable);
    $alumnoUser = User::factory()->create(['grupo_id' => $this->bekho->id, 'persona_id' => $persona->id]);
    $alumnoUser->assignRole('alumno');

    Livewire::actingAs($alumnoUser)->test(MisLogros::class)
        ->assertSee($persona->nombres);
});

test('muestra ganadas y bloqueadas (coleccionables por conseguir)', function () {
    // Gana solo 1 de los 6 coleccionables.
    $persona = personaConLogro($this->bekho, TipoRecompensa::Coleccionable);
    $alumnoUser = User::factory()->create(['grupo_id' => $this->bekho->id, 'persona_id' => $persona->id]);
    $alumnoUser->assignRole('alumno');

    Livewire::actingAs($alumnoUser)->test(MisLogros::class)
        ->assertSee('1 logro')
        ->assertSee('🔒'); // hay coleccionables aún bloqueados
});

// --- Permisos ----------------------------------------------------------------

test('mis-logros exige el permiso ver recompensas', function () {
    $alumno = User::factory()->create(['grupo_id' => $this->bekho->id]);
    $alumno->assignRole('alumno');
    $administrativo = User::factory()->create(['grupo_id' => $this->bekho->id]);
    $administrativo->assignRole('administrativo'); // no tiene ver recompensas

    Tenant::olvidar();
    $this->actingAs($alumno)->get(route('recompensas.mis-logros'))->assertOk();
    $this->actingAs($administrativo)->get(route('recompensas.mis-logros'))->assertForbidden();
});
