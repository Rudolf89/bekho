<?php

use App\Livewire\Recompensas\MisLogros;
use App\Livewire\Recompensas\PanelRecompensas;
use Illuminate\Support\Facades\Route;

// Gamificación / recompensas (otorgar logros a los alumnos).
Route::middleware(['auth', 'can:gestionar recompensas'])->group(function () {
    Route::livewire('recompensas', PanelRecompensas::class)->name('recompensas.index');
});

// Colección de logros del alumno/apoderado (solo lectura).
Route::middleware(['auth', 'can:ver recompensas'])->group(function () {
    Route::livewire('mis-logros', MisLogros::class)->name('recompensas.mis-logros');
});
