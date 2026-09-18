<?php

use App\Enums\NivelEntrenamiento;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;

beforeEach(function () {
    $this->grupo = Grupo::create(['nombre' => 'ATA', 'activo' => true]);
});

function crearEstudiante(int $grupoId, ?int $gradoId): Estudiante
{
    return Estudiante::create([
        'grupo_id' => $grupoId,
        'nombre' => 'Alumno',
        'grupo_etario' => 'jovenes_adultos',
        'grado_id' => $gradoId,
        'activo' => true,
    ]);
}

test('un alumno sin cinturón queda en Principiantes', function () {
    $est = crearEstudiante($this->grupo->id, null);

    expect($est->nivel)->toBe(NivelEntrenamiento::Principiantes);
});

test('el nivel se deriva del color del cinturón', function () {
    $blanco = Grado::create(['nombre' => 'Blanco', 'orden' => 1, 'escala' => 'adultos', 'color' => 'Blanco', 'activo' => true]);
    $verde = Grado::create(['nombre' => 'Verde Decidido', 'orden' => 8, 'escala' => 'adultos', 'color' => 'Verde', 'activo' => true]);
    $azul = Grado::create(['nombre' => 'Azul Decidido', 'orden' => 10, 'escala' => 'adultos', 'color' => 'Azul', 'activo' => true]);
    $rojo = Grado::create(['nombre' => 'Rojo Decidido', 'orden' => 16, 'escala' => 'adultos', 'color' => 'Rojo', 'activo' => true]);
    $rojoNegro = Grado::create(['nombre' => 'Rojo/Negro', 'orden' => 18, 'escala' => 'adultos', 'color' => 'Rojo/Negro', 'activo' => true]);
    $dan = Grado::create(['nombre' => '1º Dan', 'orden' => 19, 'escala' => 'adultos', 'color' => 'Negro', 'activo' => true]);

    expect(crearEstudiante($this->grupo->id, $blanco->id)->nivel)->toBe(NivelEntrenamiento::Principiantes)
        ->and(crearEstudiante($this->grupo->id, $verde->id)->nivel)->toBe(NivelEntrenamiento::Intermedio)
        ->and(crearEstudiante($this->grupo->id, $azul->id)->nivel)->toBe(NivelEntrenamiento::Avanzado)
        ->and(crearEstudiante($this->grupo->id, $rojo->id)->nivel)->toBe(NivelEntrenamiento::Avanzado)
        ->and(crearEstudiante($this->grupo->id, $rojoNegro->id)->nivel)->toBe(NivelEntrenamiento::RojoNegro)
        ->and(crearEstudiante($this->grupo->id, $dan->id)->nivel)->toBe(NivelEntrenamiento::Danes);
});

test('graduar (cambiar el cinturón) recalcula el nivel', function () {
    $amarillo = Grado::create(['nombre' => 'Amarillo Decidido', 'orden' => 4, 'escala' => 'adultos', 'color' => 'Amarillo', 'activo' => true]);
    $azul = Grado::create(['nombre' => 'Azul Decidido', 'orden' => 10, 'escala' => 'adultos', 'color' => 'Azul', 'activo' => true]);

    $est = crearEstudiante($this->grupo->id, $amarillo->id);
    expect($est->nivel)->toBe(NivelEntrenamiento::Principiantes);

    // Al graduar se actualiza grado_id; el nivel debe seguir al nuevo cinturón.
    $est->update(['grado_id' => $azul->id]);

    expect($est->fresh()->nivel)->toBe(NivelEntrenamiento::Avanzado);
});
