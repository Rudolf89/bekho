<?php

use App\Livewire\Examenes\ConteoCascada;
use App\Livewire\Examenes\DetalleConvocatoria;
use App\Livewire\Examenes\GestionConvocatorias;
use Illuminate\Support\Facades\Route;

// El conteo en cascada (collares de máster) es de gestión/supervisión.
Route::middleware(['auth', 'can:gestionar examenes'])->group(function () {
    Route::livewire('examenes/conteo', ConteoCascada::class)->name('examenes.conteo');
});

// Listado y detalle de convocatorias: los abren tanto quien gestiona exámenes
// como el instructor (que solo inscribe). Las acciones de escritura se controlan
// dentro de cada componente según el permiso.
Route::middleware(['auth', 'can:ver examenes'])->group(function () {
    Route::livewire('examenes', GestionConvocatorias::class)->name('examenes.index');
    Route::livewire('examenes/{convocatoria}', DetalleConvocatoria::class)->name('examenes.detalle');
});
