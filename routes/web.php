<?php

use App\Livewire\Panel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// La raíz no muestra una página de marketing: es un sistema privado de gestión.
// Se redirige al panel (si hay sesión) o al inicio de sesión.
Route::get('/', fn () => Auth::check()
    ? redirect()->route('dashboard')
    : redirect()->route('login'))->name('home');

// Sin middleware 'verified': el modelo User no implementa MustVerifyEmail, así
// que ese middleware no protegía nada (dejaba pasar a todos). Ver nota en User.
Route::middleware(['auth'])->group(function () {
    Route::livewire('dashboard', Panel::class)->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/formacion.php';
require __DIR__.'/cuestionarios.php';
require __DIR__.'/usuarios.php';
require __DIR__.'/gestion.php';
require __DIR__.'/examenes.php';
require __DIR__.'/planillas.php';
require __DIR__.'/recompensas.php';
require __DIR__.'/legacy.php';
require __DIR__.'/organizacion.php';
