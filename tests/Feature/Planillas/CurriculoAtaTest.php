<?php

use App\Enums\Cuadrante;
use App\Enums\RolCuadrante;
use App\Livewire\Planillas\CuadrantesEnsenanza;
use App\Models\CuadranteItem;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\User;
use Database\Seeders\CuadrantesSeeder;
use Database\Seeders\GradosSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(GradosSeeder::class);
    $this->seed(RolesPermisosSeeder::class);
    $this->seed(CuadrantesSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->bekho = Grupo::where('nombre', 'BEKHO Power Academy')->first();
});

// ── Fase 3: significado del cinturón ────────────────────────────────────────

test('los grados tienen el significado del color (filosofía Songahm)', function () {
    $blanco = Grado::where('color', 'Blanco')->first();
    $negro = Grado::where('color', 'Negro')->first();

    expect($blanco->significado)->toContain('plantarse la semilla')
        ->and($negro->significado)->toContain('madurez');
});

// ── Fase 4: cuadrantes de enseñanza ─────────────────────────────────────────

test('cada cuadrante tiene sus items de alumno e instructor', function () {
    expect(CuadranteItem::deCuadrante(Cuadrante::Estructura)->where('rol', RolCuadrante::Alumno->value)->count())->toBe(10)
        ->and(CuadranteItem::deCuadrante(Cuadrante::Estructura)->where('rol', RolCuadrante::Instructor->value)->count())->toBe(10)
        ->and(CuadranteItem::deCuadrante(Cuadrante::Legado)->where('rol', RolCuadrante::Alumno->value)->count())->toBe(6);
});

test('los items de Estructura traen su detalle', function () {
    $item = CuadranteItem::deCuadrante(Cuadrante::Estructura)
        ->where('rol', RolCuadrante::Alumno->value)
        ->where('orden', 1)
        ->first();

    expect($item->texto)->toContain('Saludar')
        ->and($item->detalle)->not->toBeNull();
});

test('la página de cuadrantes renderiza para un instructor', function () {
    $instructor = User::factory()->create(['grupo_id' => $this->bekho->id]);
    $instructor->assignRole('instructor');

    Livewire::actingAs($instructor)->test(CuadrantesEnsenanza::class)
        ->assertOk()
        ->assertSee('Estructura')
        ->assertSee('Legado')
        ->assertSee('obedecer lo que es correcto');
});
