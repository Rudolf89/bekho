<?php

use App\Enums\NivelEntrenamiento;
use App\Models\Grado;

// El nivel de entrenamiento del alumno se deriva del color de su cinturón
// (grado). La matrícula cachea ese nivel; aquí se prueba el mapeo de origen.

test('sin cinturón el nivel es Principiantes', function () {
    // Un grado Blanco (o cualquier color base) cae en Principiantes; un alumno
    // sin grado se trata igual en GestionEstudiantes::nivelDerivado.
    $blanco = new Grado(['color' => 'Blanco']);

    expect($blanco->nivelEntrenamiento())->toBe(NivelEntrenamiento::Principiantes);
});

test('el nivel se deriva del color del cinturón', function () {
    $mapa = [
        'Blanco' => NivelEntrenamiento::Principiantes,
        'Amarillo' => NivelEntrenamiento::Principiantes,
        'Verde' => NivelEntrenamiento::Intermedio,
        'Púrpura' => NivelEntrenamiento::Intermedio,
        'Azul' => NivelEntrenamiento::Avanzado,
        'Rojo' => NivelEntrenamiento::Avanzado,
        'Rojo/Negro' => NivelEntrenamiento::RojoNegro,
        'Negro' => NivelEntrenamiento::Danes,
    ];

    foreach ($mapa as $color => $nivel) {
        expect((new Grado(['color' => $color]))->nivelEntrenamiento())->toBe($nivel);
    }
});
