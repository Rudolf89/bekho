<?php

use App\Livewire\Grupos\GestionGrupos;
use App\Livewire\Sedes\GestionSedes;
use Illuminate\Support\Facades\Route;

// Sedes (permiso "gestionar sedes": admin-plataforma y maestro).
Route::middleware(['auth', 'can:gestionar sedes'])->group(function () {
    Route::livewire('sedes', GestionSedes::class)->name('sedes.index');
});

// Grupos (permiso "gestionar grupos": solo admin-plataforma).
Route::middleware(['auth', 'can:gestionar grupos'])->group(function () {
    Route::livewire('grupos', GestionGrupos::class)->name('grupos.index');
});
