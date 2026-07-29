<?php

use App\Livewire\Legacy\PanelLegacy;
use Illuminate\Support\Facades\Route;

// Programa Legacy operativo (track de formación de instructores).
Route::middleware(['auth', 'can:gestionar legacy'])->group(function () {
    Route::livewire('legacy', PanelLegacy::class)->name('legacy.index');
});
