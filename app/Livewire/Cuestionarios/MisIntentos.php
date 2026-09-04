<?php

namespace App\Livewire\Cuestionarios;

use App\Livewire\Concerns\ConTabla;
use App\Models\IntentoCuestionario;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Panel del alumno: su historial de intentos con el puntaje y el estado de la
 * revisión del examinador (en revisión / aprobado / debe reintentar).
 */
#[Title('Mis intentos')]
class MisIntentos extends Component
{
    use ConTabla, WithPagination;

    public function render()
    {
        $intentos = $this->aplicarBusqueda(
            IntentoCuestionario::query()
                ->where('user_id', Auth::id())
                ->with(['cuestionario', 'revisor']),
            ['cuestionario.titulo'],
        )->latest('finalizado_at')->paginate(15);

        return view('livewire.cuestionarios.mis-intentos', [
            'intentos' => $intentos,
        ]);
    }
}
