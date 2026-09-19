<?php

use App\Enums\EstadoMatricula;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Persona;
use App\Models\Sede;
use App\Support\Tenancy\Grupo as Tenant;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->bekho = Grupo::where('nombre', 'BEKHO Power Academy')->first();
    $this->sede = Sede::create(['grupo_id' => $this->bekho->id, 'nombre' => 'Central', 'activo' => true]);
});

afterEach(fn () => Tenant::olvidar());

test('una persona solo puede tener una matrícula activa en toda la federación', function () {
    $otra = Grupo::create(['nombre' => 'Otro Grupo', 'activo' => true]);
    $persona = Persona::create(['nombres' => 'Alumno', 'fecha_nacimiento' => now()->subYears(12)]);

    Matricula::create([
        'persona_id' => $persona->id, 'grupo_id' => $this->bekho->id, 'sede_id' => $this->sede->id,
        'estado' => EstadoMatricula::Activa->value, 'fecha_ingreso' => now(),
    ]);

    // Otra activa (aunque sea en otro grupo) rompe el índice parcial.
    expect(fn () => Matricula::create([
        'persona_id' => $persona->id, 'grupo_id' => $otra->id,
        'estado' => EstadoMatricula::Activa->value, 'fecha_ingreso' => now(),
    ]))->toThrow(QueryException::class);
});

test('una matrícula retirada no bloquea una nueva activa', function () {
    $persona = Persona::create(['nombres' => 'Retornado', 'fecha_nacimiento' => now()->subYears(20)]);

    Matricula::create([
        'persona_id' => $persona->id, 'grupo_id' => $this->bekho->id, 'sede_id' => $this->sede->id,
        'estado' => EstadoMatricula::Retirada->value, 'fecha_ingreso' => now()->subYear(), 'fecha_retiro' => now()->subMonth(),
    ]);

    $activa = Matricula::create([
        'persona_id' => $persona->id, 'grupo_id' => $this->bekho->id, 'sede_id' => $this->sede->id,
        'estado' => EstadoMatricula::Activa->value, 'fecha_ingreso' => now(),
    ]);

    expect($activa->exists)->toBeTrue()
        ->and(Matricula::where('persona_id', $persona->id)->count())->toBe(2);
});

test('la matrícula se aísla por grupo y autorrellena el grupo activo', function () {
    $otra = Grupo::create(['nombre' => 'Otro Grupo', 'activo' => true]);
    $p1 = Persona::create(['nombres' => 'Uno', 'fecha_nacimiento' => now()->subYears(10)]);
    $p2 = Persona::create(['nombres' => 'Dos', 'fecha_nacimiento' => now()->subYears(10)]);

    Matricula::withoutGlobalScopes()->create([
        'persona_id' => $p2->id, 'grupo_id' => $otra->id, 'estado' => 'activa', 'fecha_ingreso' => now(),
    ]);

    Tenant::set($this->bekho->id);
    $mia = Matricula::create([
        'persona_id' => $p1->id, 'sede_id' => $this->sede->id, 'estado' => 'activa', 'fecha_ingreso' => now(),
    ]);

    expect($mia->grupo_id)->toBe($this->bekho->id)
        ->and(Matricula::count())->toBe(1)
        ->and(Matricula::first()->persona_id)->toBe($p1->id);
});

test('la sede de la matrícula debe pertenecer al mismo grupo (FK compuesta)', function () {
    $otra = Grupo::create(['nombre' => 'Otro Grupo', 'activo' => true]);
    $sedeAjena = Sede::create(['grupo_id' => $otra->id, 'nombre' => 'Ajena', 'activo' => true]);
    $persona = Persona::create(['nombres' => 'Cruzado', 'fecha_nacimiento' => now()->subYears(15)]);

    // Matrícula en BEKHO apuntando a una sede de otro grupo: la FK compuesta lo impide.
    expect(fn () => Matricula::withoutGlobalScopes()->create([
        'persona_id' => $persona->id, 'grupo_id' => $this->bekho->id, 'sede_id' => $sedeAjena->id,
        'estado' => 'activa', 'fecha_ingreso' => now(),
    ]))->toThrow(QueryException::class);
});

test('el scope activas filtra por estado', function () {
    Tenant::set($this->bekho->id);
    $a = Persona::create(['nombres' => 'A', 'fecha_nacimiento' => now()->subYears(10)]);
    $b = Persona::create(['nombres' => 'B', 'fecha_nacimiento' => now()->subYears(10)]);

    Matricula::create(['persona_id' => $a->id, 'sede_id' => $this->sede->id, 'estado' => 'activa', 'fecha_ingreso' => now()]);
    Matricula::create(['persona_id' => $b->id, 'sede_id' => $this->sede->id, 'estado' => 'retirada', 'fecha_ingreso' => now()]);

    expect(Matricula::activas()->count())->toBe(1);
});

test('matricula_origen_id enlaza un traslado con su matrícula de origen', function () {
    Tenant::set($this->bekho->id);
    $persona = Persona::create(['nombres' => 'Trasladado', 'fecha_nacimiento' => now()->subYears(22)]);

    $origen = Matricula::create([
        'persona_id' => $persona->id, 'sede_id' => $this->sede->id,
        'estado' => 'retirada', 'fecha_ingreso' => now()->subYear(), 'motivo_baja' => 'traslado',
    ]);
    $destino = Matricula::create([
        'persona_id' => $persona->id, 'sede_id' => $this->sede->id,
        'estado' => 'activa', 'fecha_ingreso' => now(), 'matricula_origen_id' => $origen->id,
    ]);

    expect($destino->origen->id)->toBe($origen->id);
});
