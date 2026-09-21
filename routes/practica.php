<?php

use App\Livewire\Competencia\HojasPractica;
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

        // Una sola hoja con las dos pruebas lado a lado, como la planilla oficial.
        Route::get('formula', function (Datos $datos) {
            return view('practica.planillas.formula', [
                'pruebas' => $datos->pruebasConCriterios(),
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
                'rondas' => Datos::RONDAS,
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
