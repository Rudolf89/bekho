<?php

use App\Livewire\Usuarios\GestionUsuarios;
use Illuminate\Support\Facades\Route;

// Gestión de usuarios (alta de cuentas por la escuela). Requiere el permiso
// "gestionar usuarios" (super-admin y maestro).
Route::middleware(['auth', 'can:gestionar usuarios'])
    ->group(function () {
        Route::livewire('usuarios', GestionUsuarios::class)->name('usuarios.index');
    });
