<?php

use App\Livewire\Planificador\BibliotecaTecnicas;
use App\Livewire\Planificador\Cinturones;
use App\Livewire\Planificador\CuadrantesEnsenanza;
use App\Livewire\Planificador\EditarPlanificacion;
use App\Livewire\Planificador\GestionPlanificaciones;
use App\Livewire\Planificador\PlanCiclos;
use App\Livewire\Planificador\Planificador;
use Illuminate\Support\Facades\Route;

// Planificaciones de clase (permiso "gestionar planificaciones").
Route::middleware(['auth', 'can:gestionar planificaciones'])->group(function () {
    Route::livewire('ciclos', PlanCiclos::class)->name('ciclos.index');
    Route::livewire('planificador', Planificador::class)->name('planificador.index');
    Route::livewire('biblioteca', BibliotecaTecnicas::class)->name('biblioteca.index');
    Route::livewire('cinturones', Cinturones::class)->name('cinturones.index');
    Route::livewire('cuadrantes', CuadrantesEnsenanza::class)->name('cuadrantes.index');
    Route::livewire('planificaciones', GestionPlanificaciones::class)->name('planificaciones.index');
    Route::livewire('planificaciones/{planificacion}', EditarPlanificacion::class)->name('planificaciones.editar');
});
