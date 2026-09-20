<?php

use App\Enums\EstadoMatricula;
use App\Models\Matricula;
use App\Models\Persona;
use App\Models\PersonalGrupo;
use App\Models\User;
use Database\Seeders\DemoBekhoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// La capa de identidad (personas/matrículas/personal) se crea directamente en la
// siembra de demostración; ya no se deriva de una tabla estudiantes.
beforeEach(fn () => $this->seed());

test('cada usuario queda enlazado a una persona', function () {
    expect(User::count())->toBeGreaterThan(0)
        ->and(User::whereNull('persona_id')->count())->toBe(0);
});

test('la demo crea matrículas activas', function () {
    expect(Matricula::withoutGlobalScopes()->where('estado', EstadoMatricula::Activa->value)->count())
        ->toBeGreaterThan(0);
});

test('hay una persona por cada usuario y cada matrícula', function () {
    $esperadas = User::count() + Matricula::withoutGlobalScopes()->count();

    expect(Persona::count())->toBe($esperadas);
});

test('el personal con grupo queda registrado en personal_grupo', function () {
    $usuariosConGrupo = User::whereNotNull('grupo_id')->count();

    expect(PersonalGrupo::withoutGlobalScopes()->count())->toBe($usuariosConGrupo);
});

test('la siembra de demostración es idempotente (no duplica al repetir)', function () {
    $personasAntes = Persona::count();
    $matriculasAntes = Matricula::withoutGlobalScopes()->count();

    $this->seed(DemoBekhoSeeder::class);

    expect(Persona::count())->toBe($personasAntes)
        ->and(Matricula::withoutGlobalScopes()->count())->toBe($matriculasAntes);
});
