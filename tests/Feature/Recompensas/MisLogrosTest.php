<?php

use App\Enums\GrupoEtario;
use App\Enums\TipoRecompensa;
use App\Livewire\Recompensas\MisLogros;
use App\Models\Academia;
use App\Models\Estudiante;
use App\Models\Recompensa;
use App\Models\User;
use App\Support\Tenancy\Academia as Tenant;
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
    $this->bekho = Academia::where('nombre', 'BEKHO Power Academy')->first();
    Tenant::set($this->bekho->id);
});

afterEach(fn () => Tenant::olvidar());

function estudianteConLogro(Academia $a, TipoRecompensa $tipo): Estudiante
{
    $e = Estudiante::create([
        'academia_id' => $a->id, 'nombre' => 'Hijo '.uniqid(),
        'grupo_etario' => GrupoEtario::ForKids->value, 'activo' => true,
    ]);
    $recompensa = Recompensa::where('tipo', $tipo)->first();
    $e->logros()->create([
        'recompensa_id' => $recompensa->id, 'otorgado_at' => now(), 'academia_id' => $a->id,
    ]);

    return $e;
}

// --- Apoderado ---------------------------------------------------------------

test('el apoderado ve la colección de logros de sus hijos', function () {
    $apoderado = User::factory()->create(['academia_id' => $this->bekho->id]);
    $apoderado->assignRole('apoderado');

    $hijo = estudianteConLogro($this->bekho, TipoRecompensa::Coleccionable);
    $apoderado->hijos()->attach($hijo->id);

    // Hijo de OTRO apoderado (no debe verse).
    $ajeno = estudianteConLogro($this->bekho, TipoRecompensa::Coleccionable);

    Livewire::actingAs($apoderado)->test(MisLogros::class)
        ->assertSee($hijo->nombre)
        ->assertDontSee($ajeno->nombre)
        ->assertSee('Coleccionables');
});

// --- Alumno ------------------------------------------------------------------

test('el alumno ve su propia colección', function () {
    $alumnoUser = User::factory()->create(['academia_id' => $this->bekho->id]);
    $alumnoUser->assignRole('alumno');

    $ficha = estudianteConLogro($this->bekho, TipoRecompensa::Coleccionable);
    $ficha->update(['user_id' => $alumnoUser->id]);

    Livewire::actingAs($alumnoUser)->test(MisLogros::class)
        ->assertSee($ficha->nombre);
});

test('muestra ganadas y bloqueadas (coleccionables por conseguir)', function () {
    $alumnoUser = User::factory()->create(['academia_id' => $this->bekho->id]);
    $alumnoUser->assignRole('alumno');

    // Gana solo 1 de los 6 coleccionables.
    $ficha = estudianteConLogro($this->bekho, TipoRecompensa::Coleccionable);
    $ficha->update(['user_id' => $alumnoUser->id]);

    Livewire::actingAs($alumnoUser)->test(MisLogros::class)
        ->assertSee('1 logro')
        ->assertSee('🔒'); // hay coleccionables aún bloqueados
});

// --- Permisos ----------------------------------------------------------------

test('mis-logros exige el permiso ver recompensas', function () {
    $alumno = User::factory()->create(['academia_id' => $this->bekho->id]);
    $alumno->assignRole('alumno');
    $administrativo = User::factory()->create(['academia_id' => $this->bekho->id]);
    $administrativo->assignRole('administrativo'); // no tiene ver recompensas

    Tenant::olvidar();
    $this->actingAs($alumno)->get(route('recompensas.mis-logros'))->assertOk();
    $this->actingAs($administrativo)->get(route('recompensas.mis-logros'))->assertForbidden();
});
