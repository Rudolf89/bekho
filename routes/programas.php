<?php

use App\Livewire\Programas\Admin\AdminContenidos;
use App\Livewire\Programas\Admin\AdminEtapas;
use App\Livewire\Programas\GestionInscripciones;
use App\Livewire\Programas\ListaProgramas;
use App\Livewire\Programas\VerContenido;
use App\Livewire\Programas\VerPrograma;
use Illuminate\Support\Facades\Route;

// Administración del catálogo (permiso "gestionar formacion").
Route::middleware(['auth', 'can:gestionar formacion'])
    ->prefix('programas/admin')
    ->name('programas.admin.')
    ->group(function () {
        Route::livewire('etapas', AdminEtapas::class)->name('etapas');
        Route::livewire('etapas/{etapa}/contenidos', AdminContenidos::class)->name('contenidos');
    });

// Gestión de inscripciones a programas (permiso "gestionar legacy").
Route::middleware(['auth', 'can:gestionar legacy'])
    ->group(function () {
        Route::livewire('programas/gestion', GestionInscripciones::class)->name('programas.gestion');
    });

// Consumo (permiso "ver formacion").
Route::middleware(['auth', 'can:ver formacion'])
    ->prefix('programas')
    ->name('programas.')
    ->group(function () {
        Route::livewire('/', ListaProgramas::class)->name('index');
        Route::livewire('{programa}', VerPrograma::class)->name('programa');
        Route::livewire('contenidos/{contenido}', VerContenido::class)->name('contenido');
    });
