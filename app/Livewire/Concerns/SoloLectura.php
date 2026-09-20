<?php

namespace App\Livewire\Concerns;

use Illuminate\Support\Facades\Auth;

/**
 * Bloquea las acciones de escritura para los usuarios de solo lectura (rol
 * federacion). Estos usuarios ven todos los grupos para fines de supervisión,
 * pero no pueden crear ni modificar datos. Se llama al inicio de cada método que
 * muta estado.
 */
trait SoloLectura
{
    /**
     * Aborta con 403 si el usuario autenticado es de solo lectura.
     */
    protected function bloqueaSiSoloLectura(): void
    {
        abort_if(
            (bool) Auth::user()?->esSoloLectura(),
            403,
            'Tu cuenta es de solo lectura.',
        );
    }
}
