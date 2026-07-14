<?php

namespace App\Livewire;

use App\Models\Academia;
use Livewire\Component;

/**
 * Selector de "academia activa" para el admin-plataforma. Al cambiar, guarda la
 * elección en la sesión y recarga para que el tenant activo se aplique en
 * toda la aplicación (listados y creación de registros).
 */
class SelectorAcademia extends Component
{
    public string $academiaActiva = '';

    public function mount(): void
    {
        $this->academiaActiva = (string) (session('academia_activa_id') ?? '');
    }

    public function updatedAcademiaActiva(): void
    {
        session(['academia_activa_id' => $this->academiaActiva !== '' ? (int) $this->academiaActiva : null]);

        $this->redirect(request()->headers->get('referer') ?: route('dashboard'));
    }

    public function render()
    {
        return view('livewire.selector-academia', [
            'academias' => Academia::orderBy('nombre')->get(),
        ]);
    }
}
