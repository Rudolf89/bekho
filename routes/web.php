<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

// Sin middleware 'verified': el modelo User no implementa MustVerifyEmail, así
// que ese middleware no protegía nada (dejaba pasar a todos). Ver nota en User.
Route::middleware(['auth'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/formacion.php';
require __DIR__.'/usuarios.php';
require __DIR__.'/gestion.php';
require __DIR__.'/examenes.php';
require __DIR__.'/planillas.php';
require __DIR__.'/organizacion.php';
