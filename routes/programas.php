<?php

use App\Livewire\Programas\Admin\AdminContenidos;
use App\Livewire\Programas\Admin\AdminEtapas;
use App\Livewire\Programas\GestionInscripciones;
use App\Livewire\Programas\ListaProgramas;
use App\Livewire\Programas\VerContenido;
use App\Livewire\Programas\VerPrograma;
use Illuminate\Support\Facades\Route;

// Administración del catálogo (permiso "gestionar programas").
Route::middleware(['auth', 'can:gestionar programas'])
    ->prefix('programas/admin')
    ->name('programas.admin.')
    ->group(function () {
        Route::livewire('etapas', AdminEtapas::class)->name('etapas');
        Route::livewire('etapas/{etapa}/contenidos', AdminContenidos::class)->name('contenidos');
    });

// Gestión de inscripciones a programas (permiso "gestionar inscripciones").
Route::middleware(['auth', 'can:gestionar inscripciones'])
    ->group(function () {
        Route::livewire('programas/gestion', GestionInscripciones::class)->name('programas.gestion');
    });

// Consumo (permiso "ver programas").
Route::middleware(['auth', 'can:ver programas'])
    ->prefix('programas')
    ->name('programas.')
    ->group(function () {
        Route::livewire('/', ListaProgramas::class)->name('index');
        Route::livewire('{programa}', VerPrograma::class)->name('programa');
        Route::livewire('contenidos/{contenido}', VerContenido::class)->name('contenido');
    });
