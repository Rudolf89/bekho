<?php

use App\Enums\EstadoLegacy;
use App\Livewire\Programas\ProgresoLegacy;
use App\Models\InscripcionPrograma;
use App\Models\Persona;
use App\Models\Programa;
use App\Models\User;
use Database\Seeders\CuestionariosSeeder;
use Database\Seeders\EtapasProgramaSeeder;
use Database\Seeders\FederacionesSeeder;
use Database\Seeders\GradosSeeder;
use Database\Seeders\ProgramasSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(FederacionesSeeder::class);
    $this->seed(ProgramasSeeder::class);
    $this->seed(GradosSeeder::class);
    // Antes de las etapas: la prueba escrita del Nivel 3 se enlaza a este banco.
    $this->seed(CuestionariosSeeder::class);
    $this->seed(EtapasProgramaSeeder::class);
    $this->seed(RolesPermisosSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->legacy = Programa::where('nombre', 'Legacy')->first();
    $this->niveles = $this->legacy->etapas()->whereNotNull('horas_requeridas')->orderBy('orden')->get();
});

/** Usuario con persona y, si se pide, inscripción en Legacy en la etapa dada. */
function traineeLegacy(?int $etapaId = null): User
{
    $persona = Persona::create(['nombres' => 'Trainee', 'fecha_nacimiento' => now()->subYears(20)]);
    $user = User::create([
        'name' => 'Trainee', 'email' => 'trainee@bekho.cl',
        'password' => bcrypt('secreto'), 'persona_id' => $persona->id, 'activo' => true,
    ]);
    $user->assignRole('instructor');

    if ($etapaId !== null) {
        InscripcionPrograma::create([
            'persona_id' => $persona->id,
            'programa_id' => Programa::where('nombre', 'Legacy')->value('id'),
            'etapa_actual_id' => $etapaId,
            'estado' => EstadoLegacy::EnCurso->value,
            'fecha_ingreso' => now(),
        ]);
    }

    return $user;
}

test('sin inscripción muestra el aviso en vez del avance', function () {
    Livewire::actingAs(traineeLegacy())
        ->test(ProgresoLegacy::class)
        ->assertSee('No estás inscrito en el Programa Legacy')
        ->assertDontSee('Niveles del programa');
});

test('muestra los tres niveles del programa y marca el que está en curso', function () {
    Livewire::actingAs(traineeLegacy($this->niveles[0]->id))
        ->test(ProgresoLegacy::class)
        ->assertViewHas('niveles', fn ($n) => $n->count() === 3
            && $n[0]['estado'] === 'en_curso'
            && $n[1]['estado'] === 'bloqueada'
            && $n[2]['estado'] === 'bloqueada');
});

test('los niveles con ascenso quedan completados al 100 %', function () {
    $user = traineeLegacy($this->niveles[1]->id);
    $inscripcion = InscripcionPrograma::where('persona_id', $user->persona_id)->first();
    $inscripcion->ascensos()->create(['etapa_programa_id' => $this->niveles[0]->id, 'fecha' => now()]);

    Livewire::actingAs($user)
        ->test(ProgresoLegacy::class)
        ->assertViewHas('niveles', fn ($n) => $n[0]['estado'] === 'aprobada'
            && $n[0]['porcentaje'] === 100
            && $n[1]['estado'] === 'en_curso');
});

test('las horas acumuladas mueven la barra aunque no haya requisitos cumplidos', function () {
    $user = traineeLegacy($this->niveles[0]->id);
    $inscripcion = InscripcionPrograma::where('persona_id', $user->persona_id)->first();
    $inscripcion->horas()->create(['fecha' => now(), 'horas' => 50, 'origen' => 'manual']);

    // 50 de 100 h = media condición sobre (12 requisitos + 1) → 4 %.
    Livewire::actingAs($user)
        ->test(ProgresoLegacy::class)
        ->assertViewHas('resumen', fn ($r) => $r['horas'] === 50.0
            && $r['horasRequeridas'] === 100
            && $r['requisitosCumplidos'] === 0
            && $r['porcentaje'] === 4);
});

test('solo lista lo pendiente: un requisito cumplido desaparece de la tarjeta', function () {
    $user = traineeLegacy($this->niveles[0]->id);
    $inscripcion = InscripcionPrograma::where('persona_id', $user->persona_id)->first();
    $requisito = $this->niveles[0]->requisitos()->orderBy('orden')->first();

    $antes = count(Livewire::actingAs($user)->test(ProgresoLegacy::class)->viewData('pendientes'));

    $inscripcion->cumplimientos()->create(['requisito_etapa_id' => $requisito->id, 'cumplido_at' => now()]);

    Livewire::actingAs($user)
        ->test(ProgresoLegacy::class)
        ->assertViewHas('pendientes', fn ($p) => count($p) === $antes - 1)
        ->assertDontSee($requisito->descripcion);
});

test('la prueba escrita del nivel 3 se ofrece para rendir', function () {
    Livewire::actingAs(traineeLegacy($this->niveles[2]->id))
        ->test(ProgresoLegacy::class)
        ->assertViewHas('cuestionarioPendiente', fn ($c) => $c !== null)
        ->assertSee('Rendir examen escrito');
});
