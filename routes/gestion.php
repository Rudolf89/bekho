<?php

use App\Livewire\Apoderado\MisEstudiantes;
use App\Livewire\Asistencia\TomarAsistencia;
use App\Livewire\Clases\GestionClases;
use App\Livewire\Estudiantes\GestionEstudiantes;
use App\Livewire\Pagos\GestionPagos;
use App\Models\Estudiante;
use Illuminate\Support\Facades\Route;

// Gestión de estudiantes (permiso "gestionar alumnos").
Route::middleware(['auth', 'can:gestionar alumnos'])->group(function () {
    Route::livewire('estudiantes', GestionEstudiantes::class)->name('estudiantes.index');
});

// Clases y horario (permiso "gestionar clases").
Route::middleware(['auth', 'can:gestionar clases'])->group(function () {
    Route::livewire('clases', GestionClases::class)->name('clases.index');
});

// Toma de asistencia (permiso "tomar asistencia").
Route::middleware(['auth', 'can:tomar asistencia'])->group(function () {
    Route::livewire('asistencia', TomarAsistencia::class)->name('asistencia.tomar');
});

// Pagos (permiso "registrar pagos").
Route::middleware(['auth', 'can:registrar pagos'])->group(function () {
    Route::livewire('pagos', GestionPagos::class)->name('pagos.index');
});

// Vista de apoderado: solo ve a sus hijos (autorizado por EstudiantePolicy).
Route::middleware(['auth', 'can:viewAny,'.Estudiante::class])->group(function () {
    Route::livewire('mis-estudiantes', MisEstudiantes::class)->name('mis-estudiantes.index');
});
