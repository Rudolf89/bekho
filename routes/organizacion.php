<?php

use App\Livewire\Academias\GestionAcademias;
use App\Livewire\Sedes\GestionSedes;
use Illuminate\Support\Facades\Route;

// Sedes (permiso "gestionar sedes": admin-plataforma y maestro).
Route::middleware(['auth', 'can:gestionar sedes'])->group(function () {
    Route::livewire('sedes', GestionSedes::class)->name('sedes.index');
});

// Academias (permiso "gestionar academias": solo admin-plataforma).
Route::middleware(['auth', 'can:gestionar academias'])->group(function () {
    Route::livewire('academias', GestionAcademias::class)->name('academias.index');
});
