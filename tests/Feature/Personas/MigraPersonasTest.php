<?php

use App\Enums\EstadoMatricula;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\Persona;
use App\Models\PersonalGrupo;
use App\Models\User;
use Database\Seeders\MigraPersonasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// La migración de datos de la Fase 2 se prueba sobre el seed completo (deriva
// la capa de identidad desde la operación de demostración).
beforeEach(fn () => $this->seed());

test('cada usuario queda enlazado a una persona', function () {
    expect(User::count())->toBeGreaterThan(0)
        ->and(User::whereNull('persona_id')->count())->toBe(0);
});

test('cada estudiante activo se refleja como una matrícula activa', function () {
    $estudiantesActivos = Estudiante::withoutGlobalScopes()->where('activo', true)->count();

    expect($estudiantesActivos)->toBeGreaterThan(0)
        ->and(Matricula::withoutGlobalScopes()->where('estado', EstadoMatricula::Activa->value)->count())
        ->toBe($estudiantesActivos);
});

test('hay una persona por cada usuario y cada estudiante', function () {
    $esperadas = User::count() + Estudiante::withoutGlobalScopes()->count();

    expect(Persona::count())->toBe($esperadas);
});

test('el personal con grupo queda registrado en personal_grupo', function () {
    $usuariosConGrupo = User::whereNotNull('grupo_id')->count();

    expect(PersonalGrupo::withoutGlobalScopes()->count())->toBe($usuariosConGrupo);
});

test('la migración es idempotente (no duplica al volver a correr)', function () {
    $personasAntes = Persona::count();
    $matriculasAntes = Matricula::withoutGlobalScopes()->count();

    $this->seed(MigraPersonasSeeder::class);

    expect(Persona::count())->toBe($personasAntes)
        ->and(Matricula::withoutGlobalScopes()->count())->toBe($matriculasAntes);
});
