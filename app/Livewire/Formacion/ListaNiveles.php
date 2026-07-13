<?php

namespace App\Livewire\Formacion;

use App\Models\Nivel;
use App\Services\ServicioFormacion;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Formación')]
class ListaNiveles extends Component
{
    /**
     * Renderiza el listado de niveles activos con el avance del usuario.
     */
    public function render(ServicioFormacion $servicio)
    {
        $usuario = Auth::user();

        $niveles = Nivel::activos()
            ->ordenados()
            ->get()
            ->map(function (Nivel $nivel) use ($servicio, $usuario): array {
                return [
                    'modelo' => $nivel,
                    'avance' => $servicio->avanceDeNivel($usuario, $nivel),
                ];
            });

        return view('livewire.formacion.lista-niveles', [
            'niveles' => $niveles,
        ]);
    }
}
