<?php

namespace App\Livewire\Programas;

use App\Livewire\Concerns\ConTabla;
use App\Models\Programa;
use App\Services\ServicioProgramas;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Listado de programas formativos (Aprender + Legacy unificados) con el avance de
 * la persona en cada etapa.
 */
#[Title('Programas')]
class ListaProgramas extends Component
{
    use ConTabla;

    public function render(ServicioProgramas $servicio): View
    {
        $persona = Auth::user()?->persona;

        $programas = $this->aplicarBusqueda(Programa::activos()->with('etapas'), ['nombre', 'descripcion'])
            ->ordenados()
            ->get()
            ->filter(fn (Programa $p) => $p->etapas->isNotEmpty())
            ->map(function (Programa $p) use ($servicio, $persona): array {
                $total = $completados = 0;
                if ($persona) {
                    foreach ($p->etapas as $etapa) {
                        $avance = $servicio->avanceDeEtapa($persona, $etapa);
                        $total += $avance['total'];
                        $completados += $avance['completados'];
                    }
                }

                return [
                    'modelo' => $p,
                    'porcentaje' => $total > 0 ? (int) round($completados / $total * 100) : 0,
                ];
            })
            ->values();

        return view('livewire.programas.lista-programas', ['programas' => $programas]);
    }
}
