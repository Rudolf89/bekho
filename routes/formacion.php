<?php

use App\Livewire\Formacion\Admin\AdminContenidos;
use App\Livewire\Formacion\Admin\AdminNiveles;
use App\Livewire\Formacion\ListaNiveles;
use App\Livewire\Formacion\VerContenido;
use App\Livewire\Formacion\VerNivel;
use Illuminate\Support\Facades\Route;

// Administración del módulo (requiere permiso "gestionar formacion").
Route::middleware(['auth', 'can:gestionar formacion'])
    ->prefix('formacion/admin')
    ->name('formacion.admin.')
    ->group(function () {
        Route::livewire('niveles', AdminNiveles::class)->name('niveles');
        Route::livewire('niveles/{nivel}/contenidos', AdminContenidos::class)->name('contenidos');
    });

// Consumo del módulo (requiere permiso "ver formacion").
Route::middleware(['auth', 'can:ver formacion'])
    ->prefix('formacion')
    ->name('formacion.')
    ->group(function () {
        Route::livewire('/', ListaNiveles::class)->name('index');
        Route::livewire('niveles/{nivel}', VerNivel::class)->name('nivel');
        Route::livewire('contenidos/{contenido}', VerContenido::class)->name('contenido');
    });
