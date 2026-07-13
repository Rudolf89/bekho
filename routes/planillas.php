<?php

use App\Livewire\Planillas\EditarPlanilla;
use App\Livewire\Planillas\GestionPlanillas;
use Illuminate\Support\Facades\Route;

// Planillas de clase (permiso "gestionar planillas").
Route::middleware(['auth', 'can:gestionar planillas'])->group(function () {
    Route::livewire('planillas', GestionPlanillas::class)->name('planillas.index');
    Route::livewire('planillas/{planilla}', EditarPlanilla::class)->name('planillas.editar');
});
