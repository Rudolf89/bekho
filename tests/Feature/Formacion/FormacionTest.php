<?php

use App\Enums\EstadoProgreso;
use App\Livewire\Formacion\VerContenido;
use App\Models\Academia;
use App\Models\Contenido;
use App\Models\Nivel;
use App\Models\ProgresoContenido;
use App\Models\User;
use App\Services\ServicioFormacion;
use App\Support\Tenancy\Academia as Tenant;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->bekho = Academia::where('nombre', 'BEKHO Power Academy')->first();
    Tenant::olvidar();
});

afterEach(function () {
    Tenant::olvidar();
});

/**
 * Crea un usuario con un rol y una academia dados.
 */
function usuarioConRol(string $rol, ?int $academiaId): User
{
    // Los roles con 2FA obligatoria (admin-plataforma, maestro) necesitan la 2FA
    // confirmada para navegar; si no, el middleware ExigeDosFactores los redirige.
    $exige2fa = in_array($rol, config('bekho.2fa_obligatorio_para', []), true);

    $user = User::factory()->create([
        'academia_id' => $academiaId,
        'two_factor_confirmed_at' => $exige2fa ? now() : null,
    ]);
    $user->assignRole($rol);

    return $user;
}

test('un usuario sin permiso no accede a la administración', function () {
    // El rol alumno tiene "ver formacion" pero NO "gestionar formacion".
    $user = usuarioConRol('alumno', $this->bekho->id);

    $this->actingAs($user)
        ->get(route('formacion.admin.niveles'))
        ->assertForbidden();
});

test('un usuario con permiso sí accede a la administración', function () {
    $user = usuarioConRol('direccion', $this->bekho->id);

    $this->actingAs($user)
        ->get(route('formacion.admin.niveles'))
        ->assertOk();
});

test('un usuario con ver formacion ve el listado de niveles', function () {
    Nivel::create([
        'academia_id' => $this->bekho->id,
        'nombre' => 'Nivel de prueba',
        'orden' => 0,
        'activo' => true,
    ]);

    $user = usuarioConRol('alumno', $this->bekho->id);

    $this->actingAs($user)
        ->get(route('formacion.index'))
        ->assertOk()
        ->assertSee('Nivel de prueba');
});

test('marcarContenido crea y luego actualiza un único registro', function () {
    Tenant::set($this->bekho->id);

    $nivel = Nivel::create([
        'academia_id' => $this->bekho->id,
        'nombre' => 'Nivel 1',
        'orden' => 0,
        'activo' => true,
    ]);

    $contenido = Contenido::create([
        'academia_id' => $this->bekho->id,
        'nivel_id' => $nivel->id,
        'titulo' => 'Contenido 1',
        'tipo' => 'texto',
        'cuerpo' => 'Cuerpo de ejemplo',
        'orden' => 0,
        'activo' => true,
    ]);

    $user = User::factory()->create(['academia_id' => $this->bekho->id]);
    $servicio = app(ServicioFormacion::class);

    $servicio->marcarContenido($user, $contenido, EstadoProgreso::Visto);
    $progreso = $servicio->marcarContenido($user, $contenido, EstadoProgreso::Completado);

    // Un solo registro para el par (user, contenido).
    expect(
        ProgresoContenido::where('user_id', $user->id)
            ->where('contenido_id', $contenido->id)
            ->count()
    )->toBe(1);

    expect($progreso->estado)->toBe(EstadoProgreso::Completado);
    expect($progreso->visto_en)->not->toBeNull();
    expect($progreso->academia_id)->toBe($this->bekho->id);
});

test('marcarContenido como pendiente limpia visto_en', function () {
    Tenant::set($this->bekho->id);

    $nivel = Nivel::create([
        'academia_id' => $this->bekho->id,
        'nombre' => 'Nivel 1',
        'orden' => 0,
        'activo' => true,
    ]);

    $contenido = Contenido::create([
        'academia_id' => $this->bekho->id,
        'nivel_id' => $nivel->id,
        'titulo' => 'Contenido 1',
        'tipo' => 'texto',
        'cuerpo' => 'x',
        'orden' => 0,
        'activo' => true,
    ]);

    $user = User::factory()->create(['academia_id' => $this->bekho->id]);
    $servicio = app(ServicioFormacion::class);

    $servicio->marcarContenido($user, $contenido, EstadoProgreso::Completado);
    $progreso = $servicio->marcarContenido($user, $contenido, EstadoProgreso::Pendiente);

    expect($progreso->visto_en)->toBeNull();
});

test('marcarContenido funciona sin tenant activo tomando la academia del contenido', function () {
    // Simula el caso de un admin-plataforma (sin academia activa) revisando contenido.
    $nivel = Nivel::create(['academia_id' => $this->bekho->id, 'nombre' => 'Nivel 1', 'orden' => 0, 'activo' => true]);
    $contenido = Contenido::create([
        'academia_id' => $this->bekho->id,
        'nivel_id' => $nivel->id,
        'titulo' => 'Contenido 1',
        'tipo' => 'texto',
        'cuerpo' => 'x',
        'orden' => 0,
        'activo' => true,
    ]);

    $user = User::factory()->create(['academia_id' => null]);

    Tenant::olvidar();
    $progreso = app(ServicioFormacion::class)->marcarContenido($user, $contenido, EstadoProgreso::Completado);

    expect($progreso->academia_id)->toBe($this->bekho->id);
});

test('el global scope filtra los niveles por academia', function () {
    $otra = Academia::create(['nombre' => 'OTRA', 'activo' => true]);

    Nivel::create(['academia_id' => $this->bekho->id, 'nombre' => 'De BEKHO', 'orden' => 0, 'activo' => true]);
    Nivel::create(['academia_id' => $otra->id, 'nombre' => 'De OTRA', 'orden' => 0, 'activo' => true]);

    Tenant::set($this->bekho->id);
    expect(Nivel::count())->toBe(1);
    expect(Nivel::first()->nombre)->toBe('De BEKHO');

    Tenant::set($otra->id);
    expect(Nivel::count())->toBe(1);
    expect(Nivel::first()->nombre)->toBe('De OTRA');

    // Sin academia activa se ven todos.
    Tenant::olvidar();
    expect(Nivel::count())->toBe(2);

    // El scope sinAcademia() también ignora el filtro.
    Tenant::set($this->bekho->id);
    expect(Nivel::sinAcademia()->count())->toBe(2);
});

test('avanceDeNivel calcula el porcentaje de completados', function () {
    Tenant::set($this->bekho->id);

    $nivel = Nivel::create(['academia_id' => $this->bekho->id, 'nombre' => 'Nivel 1', 'orden' => 0, 'activo' => true]);

    $contenidos = collect(range(1, 4))->map(fn ($i) => Contenido::create([
        'academia_id' => $this->bekho->id,
        'nivel_id' => $nivel->id,
        'titulo' => "Contenido {$i}",
        'tipo' => 'texto',
        'cuerpo' => 'x',
        'orden' => $i,
        'activo' => true,
    ]));

    $user = User::factory()->create(['academia_id' => $this->bekho->id]);
    $servicio = app(ServicioFormacion::class);

    // Completa 1 de 4.
    $servicio->marcarContenido($user, $contenidos->first(), EstadoProgreso::Completado);

    $avance = $servicio->avanceDeNivel($user, $nivel);

    expect($avance['total'])->toBe(4);
    expect($avance['completados'])->toBe(1);
    expect($avance['porcentaje'])->toBe(25);
});

// --- Navegación entre capítulos (VerContenido) -------------------------------

test('la navegación avanza al siguiente capítulo y marca completado', function () {
    Tenant::set($this->bekho->id);

    $nivel = Nivel::create(['academia_id' => $this->bekho->id, 'nombre' => 'Manual X', 'orden' => 0, 'activo' => true]);
    $caps = collect(range(0, 2))->map(fn ($i) => Contenido::create([
        'academia_id' => $this->bekho->id, 'nivel_id' => $nivel->id,
        'titulo' => "Capítulo {$i}", 'tipo' => 'texto', 'cuerpo' => 'x', 'orden' => $i, 'activo' => true,
    ]));

    $user = usuarioConRol('alumno', $this->bekho->id);

    // Primer capítulo: sin anterior, con siguiente; "completar y continuar" lleva al 2.º.
    Livewire::actingAs($user)->test(VerContenido::class, ['contenido' => $caps[0]])
        ->assertViewHas('anterior', null)
        ->assertViewHas('siguiente', fn ($s) => $s?->id === $caps[1]->id)
        ->call('completarYSeguir')
        ->assertRedirect(route('formacion.contenido', $caps[1]));

    expect($user->progresoEn($caps[0]->fresh()))->toBe(EstadoProgreso::Completado);

    // Último capítulo: "completar y terminar" vuelve al nivel.
    Livewire::actingAs($user)->test(VerContenido::class, ['contenido' => $caps[2]])
        ->assertViewHas('siguiente', null)
        ->call('completarYSeguir')
        ->assertRedirect(route('formacion.nivel', $nivel->id));
});
