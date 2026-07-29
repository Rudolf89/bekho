<?php

use App\Livewire\Cuestionarios\EditarCuestionario;
use App\Livewire\Cuestionarios\ListaCuestionarios;
use App\Livewire\Cuestionarios\RendirCuestionario;
use Illuminate\Support\Facades\Route;

// Módulo de cuestionarios (evaluaciones autocorregidas, catálogo transversal).
Route::middleware(['auth'])->group(function () {
    // Rendir: cualquiera con permiso "rendir cuestionarios".
    Route::middleware('can:rendir cuestionarios')->group(function () {
        Route::livewire('cuestionarios', ListaCuestionarios::class)->name('cuestionarios.index');
        Route::livewire('cuestionarios/{cuestionario}/rendir', RendirCuestionario::class)->name('cuestionarios.rendir');
    });

    // Crear/editar: examinador ("gestionar cuestionarios").
    Route::middleware('can:gestionar cuestionarios')->group(function () {
        Route::livewire('cuestionarios/nuevo', EditarCuestionario::class)->name('cuestionarios.crear');
        Route::livewire('cuestionarios/{cuestionario}/editar', EditarCuestionario::class)->name('cuestionarios.editar');
    });
});
