<?php

use App\Enums\EstadoProgreso;
use App\Livewire\Programas\VerContenido;
use App\Models\EtapaPrograma;
use App\Models\Grupo;
use App\Models\Persona;
use App\Models\Programa;
use App\Models\ProgresoContenido;
use App\Models\User;
use App\Services\ServicioProgramas;
use App\Support\Tenancy\Grupo as Tenant;
use Database\Seeders\FederacionesSeeder;
use Database\Seeders\ProgramasSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    $this->seed(FederacionesSeeder::class);
    $this->seed(ProgramasSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->bekho = Grupo::where('nombre', 'BEKHO Power Academy')->first();
    Tenant::olvidar();
});

afterEach(fn () => Tenant::olvidar());

function usuarioConRol(string $rol, ?int $grupoId): User
{
    $exige2fa = in_array($rol, config('bekho.2fa_obligatorio_para', []), true);
    $persona = Persona::create(['nombres' => 'U '.uniqid(), 'fecha_nacimiento' => now()->subYears(20)]);
    $user = User::factory()->create([
        'grupo_id' => $grupoId,
        'persona_id' => $persona->id,
        'two_factor_confirmed_at' => $exige2fa ? now() : null,
    ]);
    $user->assignRole($rol);

    return $user;
}

/** Programa con una etapa y N contenidos de texto. */
function etapaConContenidos(int $n = 1): EtapaPrograma
{
    $programa = Programa::where('nombre', 'Legacy')->first();
    $etapa = EtapaPrograma::create(['programa_id' => $programa->id, 'nombre' => 'Etapa '.uniqid(), 'orden' => 0, 'activo' => true]);

    foreach (range(1, $n) as $i) {
        $etapa->contenidos()->create(['titulo' => "Contenido {$i}", 'tipo' => 'texto', 'cuerpo' => 'x', 'orden' => $i, 'activo' => true]);
    }

    return $etapa;
}

test('un usuario sin permiso no accede a la administración', function () {
    $user = usuarioConRol('alumno', $this->bekho->id);

    $this->actingAs($user)->get(route('programas.admin.etapas'))->assertForbidden();
});

test('un usuario con permiso sí accede a la administración', function () {
    $user = usuarioConRol('direccion', $this->bekho->id);

    $this->actingAs($user)->get(route('programas.admin.etapas'))->assertOk();
});

test('un usuario con ver formacion ve el listado de programas', function () {
    etapaConContenidos();
    $user = usuarioConRol('alumno', $this->bekho->id);

    $this->actingAs($user)->get(route('programas.index'))->assertOk()->assertSee('Legacy');
});

test('marcarContenido crea y luego actualiza un único registro por persona', function () {
    $etapa = etapaConContenidos();
    $contenido = $etapa->contenidos()->first();
    $persona = Persona::create(['nombres' => 'Alumno', 'fecha_nacimiento' => now()->subYears(12)]);

    $servicio = app(ServicioProgramas::class);
    $servicio->marcarContenido($persona, $contenido, EstadoProgreso::Visto);
    $progreso = $servicio->marcarContenido($persona, $contenido, EstadoProgreso::Completado);

    expect(ProgresoContenido::where('persona_id', $persona->id)->where('contenido_id', $contenido->id)->count())->toBe(1)
        ->and($progreso->estado)->toBe(EstadoProgreso::Completado)
        ->and($progreso->visto_en)->not->toBeNull();
});

test('avanceDeEtapa calcula el porcentaje de completados', function () {
    $etapa = etapaConContenidos(4);
    $persona = Persona::create(['nombres' => 'Alumno', 'fecha_nacimiento' => now()->subYears(12)]);
    $servicio = app(ServicioProgramas::class);

    $servicio->marcarContenido($persona, $etapa->contenidos()->first(), EstadoProgreso::Completado);

    $avance = $servicio->avanceDeEtapa($persona, $etapa);
    expect($avance['total'])->toBe(4)
        ->and($avance['completados'])->toBe(1)
        ->and($avance['porcentaje'])->toBe(25);
});

test('la navegación avanza al siguiente capítulo y marca completado', function () {
    $etapa = etapaConContenidos(3);
    $caps = $etapa->contenidos()->orderBy('orden')->get();
    $user = usuarioConRol('alumno', $this->bekho->id);

    Livewire::actingAs($user)->test(VerContenido::class, ['contenido' => $caps[0]])
        ->assertViewHas('anterior', null)
        ->assertViewHas('siguiente', fn ($s) => $s?->id === $caps[1]->id)
        ->call('completarYSeguir')
        ->assertRedirect(route('programas.contenido', $caps[1]));

    expect($user->progresoEn($caps[0]->fresh()))->toBe(EstadoProgreso::Completado);

    Livewire::actingAs($user)->test(VerContenido::class, ['contenido' => $caps[2]])
        ->assertViewHas('siguiente', null)
        ->call('completarYSeguir')
        ->assertRedirect(route('programas.programa', $etapa->programa_id));
});
