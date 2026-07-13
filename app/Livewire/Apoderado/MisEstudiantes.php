<?php

namespace App\Livewire\Apoderado;

use App\Services\ServicioPagos;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Vista del apoderado: solo ve a sus propios hijos. El aislamiento se apoya en
 * la relación hijos() del usuario y en EstudiantePolicy (no en filtros ad hoc).
 */
#[Title('Mis estudiantes')]
class MisEstudiantes extends Component
{
    public function render(ServicioPagos $servicio)
    {
        $hijos = Auth::user()->hijos()->with(['grado', 'sede'])->orderBy('nombre')->get();

        $estados = $hijos->mapWithKeys(fn ($hijo) => [
            $hijo->id => $servicio->estaMoroso($hijo) ? 'moroso' : 'al_dia',
        ]);

        return view('livewire.apoderado.mis-estudiantes', [
            'hijos' => $hijos,
            'estados' => $estados,
        ]);
    }
}
