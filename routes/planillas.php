<?php

use App\Livewire\Planillas\BibliotecaTecnicas;
use App\Livewire\Planillas\CuadrantesEnsenanza;
use App\Livewire\Planillas\EditarPlanilla;
use App\Livewire\Planillas\GestionPlanillas;
use App\Livewire\Planillas\Planificador;
use Illuminate\Support\Facades\Route;

// Planillas de clase (permiso "gestionar planillas").
Route::middleware(['auth', 'can:gestionar planillas'])->group(function () {
    Route::livewire('planificador', Planificador::class)->name('planificador.index');
    Route::livewire('biblioteca', BibliotecaTecnicas::class)->name('biblioteca.index');
    Route::livewire('cuadrantes', CuadrantesEnsenanza::class)->name('cuadrantes.index');
    Route::livewire('planillas', GestionPlanillas::class)->name('planillas.index');
    Route::livewire('planillas/{planilla}', EditarPlanilla::class)->name('planillas.editar');
});
