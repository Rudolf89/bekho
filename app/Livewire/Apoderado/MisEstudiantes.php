<?php

namespace App\Livewire\Apoderado;

use App\Models\Matricula;
use App\Services\ServicioPagos;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Vista del apoderado: solo ve las matrículas de las personas que tutela. El
 * aislamiento se apoya en Matricula::visiblePara (tutelas), no en filtros ad hoc.
 */
#[Title('Mis estudiantes')]
class MisEstudiantes extends Component
{
    public function render(ServicioPagos $servicio)
    {
        $matriculas = Matricula::visiblePara(Auth::user())
            ->with(['persona.grado', 'sede'])
            ->get();

        $estados = $matriculas->mapWithKeys(fn (Matricula $m) => [
            $m->id => $servicio->estaMoroso($m) ? 'moroso' : 'al_dia',
        ]);

        return view('livewire.apoderado.mis-estudiantes', [
            'matriculas' => $matriculas,
            'estados' => $estados,
        ]);
    }
}
