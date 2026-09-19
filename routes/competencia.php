<?php

use App\Livewire\Competencia\PlanillasCompetencia;
use App\Livewire\Competencia\VerPlanillaCompetencia;
use Illuminate\Support\Facades\Route;

// Planillas de competencia (certificación de planillero). Permiso "gestionar
// competencia": dirección, instructor, admin-plataforma y federación (lectura).
Route::middleware(['auth', 'can:gestionar competencia'])->group(function () {
    Route::livewire('competencia', PlanillasCompetencia::class)->name('competencia.index');
    Route::livewire('competencia/{planilla}', VerPlanillaCompetencia::class)->name('competencia.ver');
});
