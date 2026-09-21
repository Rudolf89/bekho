<?php

namespace App\Livewire\Cuestionarios;

use App\Enums\EstadoIntento;
use App\Livewire\Concerns\ConTabla;
use App\Models\Cuestionario;
use App\Models\IntentoCuestionario;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Índice de cuestionarios (evaluaciones autocorregidas). Cualquiera con permiso
 * "rendir cuestionarios" ve los activos y su mejor puntaje; el examinador
 * ("gestionar cuestionarios") ve además todos y puede crearlos/editarlos.
 */
#[Title('Cuestionarios')]
class ListaCuestionarios extends Component
{
    use ConTabla;

    public function eliminar(int $id): void
    {
        abort_unless(Auth::user()->can('gestionar cuestionarios'), 403);

        Cuestionario::whereKey($id)->delete();
    }

    public function render(): View
    {
        $puedeGestionar = Auth::user()->can('gestionar cuestionarios');

        $cuestionarios = $this->aplicarBusqueda(
            Cuestionario::query()
                ->when(! $puedeGestionar, fn ($q) => $q->activos())
                ->withCount('preguntas'),
            ['titulo', 'area'],
        )->ordenados()->get();

        // Mejor porcentaje del usuario por cuestionario.
        $mejores = IntentoCuestionario::query()
            ->where('user_id', Auth::id())
            ->selectRaw('cuestionario_id, max(porcentaje) as mejor')
            ->groupBy('cuestionario_id')
            ->pluck('mejor', 'cuestionario_id');

        // Cuestionarios que el examinador ya aprobó al usuario.
        $aprobados = IntentoCuestionario::query()
            ->where('user_id', Auth::id())
            ->where('estado', EstadoIntento::Aprobado)
            ->pluck('cuestionario_id')
            ->all();

        return view('livewire.cuestionarios.lista-cuestionarios', [
            'cuestionarios' => $cuestionarios,
            'mejores' => $mejores,
            'aprobados' => $aprobados,
            'puedeGestionar' => $puedeGestionar,
        ]);
    }
}
