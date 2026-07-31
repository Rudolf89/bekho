<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Bandeja de notificaciones del usuario (canal database): p. ej. la decisión del
 * examinador sobre un intento de cuestionario. Cualquier usuario autenticado la ve.
 */
#[Title('Notificaciones')]
class Notificaciones extends Component
{
    public function marcarLeida(string $id): void
    {
        Auth::user()->notifications()->where('id', $id)->first()?->markAsRead();
    }

    public function marcarTodasLeidas(): void
    {
        Auth::user()->unreadNotifications->markAsRead();
    }

    public function eliminar(string $id): void
    {
        Auth::user()->notifications()->where('id', $id)->delete();
    }

    public function render()
    {
        return view('livewire.notificaciones', [
            'notificaciones' => Auth::user()->notifications()->latest()->limit(50)->get(),
            'noLeidas' => Auth::user()->unreadNotifications()->count(),
        ]);
    }
}
