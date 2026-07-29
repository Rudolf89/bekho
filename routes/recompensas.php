<?php

use App\Livewire\Recompensas\PanelRecompensas;
use Illuminate\Support\Facades\Route;

// Gamificación / recompensas (otorgar logros a los alumnos).
Route::middleware(['auth', 'can:gestionar recompensas'])->group(function () {
    Route::livewire('recompensas', PanelRecompensas::class)->name('recompensas.index');
});
