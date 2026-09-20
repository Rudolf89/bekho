<?php

namespace App\Livewire\Programas;

use App\Models\Programa;
use App\Services\ServicioProgramas;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Detalle de un programa: sus etapas, con el avance de la persona en cada una y
 * los requisitos de avance (para los programas con requisitos, como Legacy).
 */
#[Title('Programa')]
class VerPrograma extends Component
{
    public Programa $programa;

    public function mount(Programa $programa): void
    {
        abort_unless($programa->activo, 404);

        $this->programa = $programa;
    }

    public function render(ServicioProgramas $servicio)
    {
        $persona = Auth::user()?->persona;

        $etapas = $this->programa->etapas()->where('activo', true)->with('requisitos')->get()
            ->map(fn ($etapa) => [
                'modelo' => $etapa,
                'avance' => $persona
                    ? $servicio->avanceDeEtapa($persona, $etapa)
                    : ['total' => $etapa->contenidos()->where('activo', true)->count(), 'completados' => 0, 'porcentaje' => 0],
            ]);

        return view('livewire.programas.ver-programa', ['etapas' => $etapas]);
    }
}
