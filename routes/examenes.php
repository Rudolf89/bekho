<?php

use App\Livewire\Examenes\ConteoCascada;
use App\Livewire\Examenes\DetalleConvocatoria;
use App\Livewire\Examenes\GestionConvocatorias;
use Illuminate\Support\Facades\Route;

// Exámenes de grado (permiso "gestionar examenes").
Route::middleware(['auth', 'can:gestionar examenes'])->group(function () {
    Route::livewire('examenes', GestionConvocatorias::class)->name('examenes.index');
    Route::livewire('examenes/conteo', ConteoCascada::class)->name('examenes.conteo');
    Route::livewire('examenes/{convocatoria}', DetalleConvocatoria::class)->name('examenes.detalle');
});
