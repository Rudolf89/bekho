<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Bandeja de notificaciones del usuario (canal database): p. ej. la decisión del
 * examinador sobre un intento de cuestionario. Cualquier usuario autenticado la ve.
 */
#[Title('Notificaciones')]
class Notificaciones extends Component
{
    /** Filtro: mostrar solo las no leídas. */
    public bool $soloNoLeidas = false;

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

    public function render(): View
    {
        $notificaciones = Auth::user()->notifications()
            ->when($this->soloNoLeidas, fn ($q) => $q->whereNull('read_at'))
            ->latest()
            ->limit(50)
            ->get();

        return view('livewire.notificaciones', [
            'notificaciones' => $notificaciones,
            'noLeidas' => Auth::user()->unreadNotifications()->count(),
        ]);
    }
}
