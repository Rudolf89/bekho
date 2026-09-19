<?php

namespace App\Livewire\Apoderado;

use App\Models\Matricula;
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

        // Morosidad por la matrícula activa del hijo (la asistencia y los pagos
        // van por matrícula desde el rediseño Fase 4).
        $matriculas = Matricula::withoutGlobalScopes()->activas()
            ->whereIn('estudiante_id', $hijos->pluck('id'))
            ->get()->keyBy('estudiante_id');

        $estados = $hijos->mapWithKeys(function ($hijo) use ($servicio, $matriculas) {
            $matricula = $matriculas->get($hijo->id);

            return [$hijo->id => $matricula && $servicio->estaMoroso($matricula) ? 'moroso' : 'al_dia'];
        });

        return view('livewire.apoderado.mis-estudiantes', [
            'hijos' => $hijos,
            'estados' => $estados,
        ]);
    }
}
