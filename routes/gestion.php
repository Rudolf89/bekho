<?php

use App\Livewire\Apoderado\MisEstudiantes;
use App\Livewire\Asistencia\TomarAsistencia;
use App\Livewire\Clases\GestionClases;
use App\Livewire\Cobros\GestionTarifas;
use App\Livewire\Estudiantes\GestionEstudiantes;
use App\Livewire\Estudiantes\VerMatricula;
use App\Livewire\Inscripcion\InscribirAlumno;
use App\Livewire\Pagos\GestionPagos;
use App\Models\Matricula;
use Illuminate\Support\Facades\Route;

// Inscripción de alumnos (permiso "gestionar alumnos": dirección/administrativo).
Route::middleware(['auth', 'can:gestionar alumnos'])->group(function () {
    Route::livewire('inscripcion', InscribirAlumno::class)->name('inscripcion.crear');
});

// Listado de alumnos (matrículas): gestores ven todo su tenant; el instructor
// solo las de sus clases; el apoderado las de sus hijos (MatriculaPolicy).
Route::middleware(['auth', 'can:viewAny,'.Matricula::class])->group(function () {
    Route::livewire('estudiantes', GestionEstudiantes::class)->name('estudiantes.index');
    Route::livewire('estudiantes/{matricula}', VerMatricula::class)->name('estudiantes.ver');
});

// Clases y horario (permiso "gestionar clases").
Route::middleware(['auth', 'can:gestionar clases'])->group(function () {
    Route::livewire('clases', GestionClases::class)->name('clases.index');
});

// Toma de asistencia (permiso "tomar asistencia").
Route::middleware(['auth', 'can:tomar asistencia'])->group(function () {
    Route::livewire('asistencia', TomarAsistencia::class)->name('asistencia.tomar');
});

// Pagos y tarifas (permiso "registrar pagos").
Route::middleware(['auth', 'can:registrar pagos'])->group(function () {
    Route::livewire('pagos', GestionPagos::class)->name('pagos.index');
    Route::livewire('tarifas', GestionTarifas::class)->name('tarifas.index');
});

// Vista de apoderado: solo ve las matrículas de sus hijos (MatriculaPolicy).
Route::middleware(['auth', 'can:viewAny,'.Matricula::class])->group(function () {
    Route::livewire('mis-estudiantes', MisEstudiantes::class)->name('mis-estudiantes.index');
});
