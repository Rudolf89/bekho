<?php

use App\Livewire\Planillas\GestionPlanillas;
use App\Models\Planilla;
use Illuminate\Support\Facades\Route;

// Planillas imprimibles del programa. Verlas e imprimirlas va con el permiso de
// planificaciones (instructores incluidos); el catálogo es de la federación, así
// que crear/editar exige "gestionar programas" (se comprueba en el componente).
Route::middleware(['auth', 'can:gestionar planificaciones'])->group(function () {
    Route::livewire('planillas', GestionPlanillas::class)->name('planillas.index');

    // Hoja en blanco para imprimir: sin el layout de la app, para que salga limpia.
    Route::get('planillas/{planilla}/imprimir', function (Planilla $planilla) {
        abort_if($planilla->columnas()->count() === 0, 404, 'La planilla todavía no tiene columnas.');

        return view('planillas.imprimir', ['planilla' => $planilla->load('columnas')]);
    })->name('planillas.imprimir');
});
