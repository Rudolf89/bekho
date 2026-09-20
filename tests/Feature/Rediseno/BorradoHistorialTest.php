<?php

use App\Models\Cargo;
use App\Models\DocumentoPersona;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Persona;
use App\Models\Sede;
use App\Models\TipoCargo;
use App\Support\Tenancy\Grupo as Tenant;
use Database\Seeders\CatalogosFederacionSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Regla del rediseño "sin cascadas": las FK del historial usan restrictOnDelete.
 * Un borrado en duro (forceDelete) de una persona o matrícula con historial DEBE
 * fallar; el camino correcto es la baja lógica (SoftDeletes) o la desactivación.
 */
beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    $this->seed(CatalogosFederacionSeeder::class); // tipos_cargo
    $this->bekho = Grupo::where('nombre', 'BEKHO Power Academy')->first();
    $this->sede = Sede::create(['grupo_id' => $this->bekho->id, 'nombre' => 'Central', 'activo' => true]);
    $this->mensualidad = TipoCargo::where('recurrente', true)->orderBy('orden')->first();
    Tenant::set($this->bekho->id);
});

afterEach(fn () => Tenant::olvidar());

function personaConMatricula(int $grupoId, int $sedeId): array
{
    $persona = Persona::create(['nombres' => 'Alumno', 'fecha_nacimiento' => now()->subYears(11)]);

    $matricula = Matricula::create([
        'grupo_id' => $grupoId, 'persona_id' => $persona->id, 'sede_id' => $sedeId,
        'grupo_etario' => 'for_kids', 'estado' => 'activa', 'fecha_ingreso' => now(),
    ]);

    return [$persona, $matricula];
}

test('no se puede borrar en duro una persona con matrícula (restrictOnDelete)', function () {
    [$persona] = personaConMatricula($this->bekho->id, $this->sede->id);

    expect(fn () => $persona->forceDelete())->toThrow(QueryException::class);

    // La persona sigue en la base: el borrado quedó bloqueado.
    expect(Persona::withTrashed()->whereKey($persona->id)->exists())->toBeTrue();
});

test('no se puede borrar en duro una persona con documento (restrictOnDelete)', function () {
    $persona = Persona::create(['nombres' => 'Alumno', 'fecha_nacimiento' => now()->subYears(11)]);
    DocumentoPersona::create([
        'persona_id' => $persona->id, 'tipo' => 'rut', 'numero' => '12345678-5', 'principal' => true,
    ]);

    expect(fn () => $persona->forceDelete())->toThrow(QueryException::class);
});

test('no se puede borrar en duro una matrícula con historial (cargo)', function () {
    [, $matricula] = personaConMatricula($this->bekho->id, $this->sede->id);
    Cargo::withoutGlobalScopes()->create([
        'grupo_id' => $matricula->grupo_id, 'matricula_id' => $matricula->id,
        'tipo_cargo_id' => $this->mensualidad->id, 'periodo' => now()->startOfMonth(),
        'monto' => 30000, 'estado' => 'pendiente',
    ]);

    expect(fn () => $matricula->forceDelete())->toThrow(QueryException::class);
    expect(Matricula::withTrashed()->whereKey($matricula->id)->exists())->toBeTrue();
});

test('el soft delete de una persona funciona y conserva el historial', function () {
    [$persona, $matricula] = personaConMatricula($this->bekho->id, $this->sede->id);
    $cargo = Cargo::withoutGlobalScopes()->create([
        'grupo_id' => $matricula->grupo_id, 'matricula_id' => $matricula->id,
        'tipo_cargo_id' => $this->mensualidad->id, 'periodo' => now()->startOfMonth(),
        'monto' => 30000, 'estado' => 'pendiente',
    ]);

    // La baja lógica no arrastra nada: no hay cascada al hacer soft delete.
    $persona->delete();

    expect($persona->fresh()->trashed())->toBeTrue()
        // El historial queda intacto (la matrícula y su cargo siguen existiendo).
        ->and(Matricula::withTrashed()->whereKey($matricula->id)->exists())->toBeTrue()
        ->and(Cargo::withoutGlobalScopes()->whereKey($cargo->id)->exists())->toBeTrue();
});

test('restaurar la persona recupera todo su historial', function () {
    [$persona, $matricula] = personaConMatricula($this->bekho->id, $this->sede->id);
    $cargo = Cargo::withoutGlobalScopes()->create([
        'grupo_id' => $matricula->grupo_id, 'matricula_id' => $matricula->id,
        'tipo_cargo_id' => $this->mensualidad->id, 'periodo' => now()->startOfMonth(),
        'monto' => 30000, 'estado' => 'pendiente',
    ]);

    $persona->delete();
    $persona->restore();

    expect($persona->fresh()->trashed())->toBeFalse()
        ->and(Persona::whereKey($persona->id)->exists())->toBeTrue()
        ->and(Matricula::whereKey($matricula->id)->exists())->toBeTrue()
        ->and(Cargo::withoutGlobalScopes()->whereKey($cargo->id)->exists())->toBeTrue();
});
