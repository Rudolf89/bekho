<?php

use App\Livewire\Academias\GestionAcademias;
use App\Livewire\Sedes\GestionSedes;
use Illuminate\Support\Facades\Route;

// Sedes (permiso "gestionar sedes": super-admin y maestro).
Route::middleware(['auth', 'can:gestionar sedes'])->group(function () {
    Route::livewire('sedes', GestionSedes::class)->name('sedes.index');
});

// Academias (permiso "gestionar academias": solo super-admin).
Route::middleware(['auth', 'can:gestionar academias'])->group(function () {
    Route::livewire('academias', GestionAcademias::class)->name('academias.index');
});
