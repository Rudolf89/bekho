<?php

use App\Livewire\Competencia\HojasPractica;
use App\Models\Prueba;
use App\Support\Competencia\HojasPractica as Datos;
use Illuminate\Support\Facades\Route;

// Hojas en blanco de la planilla de competencia para practicar el llenado a
// mano. Permiso "ver programas": practican aspirantes a planillero, jueces y
// alumnos Legacy, no solo quien opera un torneo.
Route::middleware(['auth', 'can:ver programas'])
    ->prefix('practica/planillas')
    ->name('practica.planillas.')
    ->group(function () {
        Route::livewire('/', HojasPractica::class)->name('index');

        Route::get('formula/{prueba}', function (Prueba $prueba, Datos $datos) {
            abort_if($prueba->criterios()->count() === 0, 404, 'Esa prueba no se puntúa con jueces.');

            return view('practica.planillas.formula', [
                'prueba' => $prueba->load('criterios'),
                'gruposEdad' => $datos->gruposEdad(),
                'categorias' => $datos->categorias(),
                'competidores' => Datos::COMPETIDORES,
            ]);
        })->name('formula');

        Route::get('sparring', function (Datos $datos) {
            return view('practica.planillas.sparring', [
                'tablaLibres' => $datos->tablaLibres(),
                'gruposEdad' => $datos->gruposEdad(),
                'categorias' => $datos->categorias(),
                'competidores' => Datos::COMPETIDORES,
                // Llave de 16: octavos → cuartos → semifinal → final.
                'rondas' => ['1.ª ronda', 'Cuartos', 'Semifinal', 'Final'],
            ]);
        })->name('sparring');

        Route::get('medallas', function (Datos $datos) {
            return view('practica.planillas.medallas', [
                'gruposEdad' => $datos->gruposEdad(),
                'categorias' => $datos->categorias(),
                'filas' => 24,
            ]);
        })->name('medallas');
    });
