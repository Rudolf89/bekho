<?php

use App\Livewire\Cuestionarios\EditarCuestionario;
use App\Livewire\Cuestionarios\ListaCuestionarios;
use App\Livewire\Cuestionarios\MisIntentos;
use App\Livewire\Cuestionarios\RendirCuestionario;
use App\Livewire\Cuestionarios\ResultadosCuestionarios;
use Illuminate\Support\Facades\Route;

// Módulo de cuestionarios (evaluaciones autocorregidas, catálogo transversal).
Route::middleware(['auth'])->group(function () {
    // Rendir: cualquiera con permiso "rendir cuestionarios".
    Route::middleware('can:rendir cuestionarios')->group(function () {
        Route::livewire('cuestionarios', ListaCuestionarios::class)->name('cuestionarios.index');
        Route::livewire('cuestionarios/mis-intentos', MisIntentos::class)->name('cuestionarios.mis-intentos');
        Route::livewire('cuestionarios/{cuestionario}/rendir', RendirCuestionario::class)->name('cuestionarios.rendir');
    });

    // Crear/editar y revisar resultados: examinador ("gestionar cuestionarios").
    Route::middleware('can:gestionar cuestionarios')->group(function () {
        Route::livewire('cuestionarios/nuevo', EditarCuestionario::class)->name('cuestionarios.crear');
        Route::livewire('cuestionarios/resultados', ResultadosCuestionarios::class)->name('cuestionarios.resultados');
        Route::livewire('cuestionarios/{cuestionario}/editar', EditarCuestionario::class)->name('cuestionarios.editar');
    });
});
