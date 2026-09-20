<?php

namespace App\Livewire;

use App\Models\Grupo;
use Livewire\Component;

/**
 * Selector de "grupo activo" para el admin-plataforma. Al cambiar, guarda la
 * elección en la sesión y recarga para que el tenant activo se aplique en
 * toda la aplicación (listados y creación de registros).
 */
class SelectorGrupo extends Component
{
    public string $grupoActivo = '';

    public function mount(): void
    {
        $this->grupoActivo = (string) (session('grupo_activa_id') ?? '');
    }

    public function updatedGrupoActivo(): void
    {
        session(['grupo_activa_id' => $this->grupoActivo !== '' ? (int) $this->grupoActivo : null]);

        $this->redirect(request()->headers->get('referer') ?: route('dashboard'));
    }

    public function render()
    {
        return view('livewire.selector-grupo', [
            'grupos' => Grupo::orderBy('nombre')->get(),
        ]);
    }
}
