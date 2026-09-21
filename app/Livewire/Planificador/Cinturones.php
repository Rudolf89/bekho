<?php

namespace App\Livewire\Planificador;

use App\Enums\EscalaGrado;
use App\Models\Grado;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Referencia de cinturones: por escala, cada grado con su color, tipo
 * (recomendado/decidido/dan), franjas, significado (filosofía Songahm) y las
 * técnicas del currículo que le corresponden.
 */
#[Title('Cinturones')]
class Cinturones extends Component
{
    #[Url]
    public string $escala = 'adultos';

    public function render(): View
    {
        $escala = EscalaGrado::tryFrom($this->escala) ?? EscalaGrado::Adultos;

        $grados = Grado::porEscala($escala)
            ->ordenados()
            ->with(['tecnicas' => fn ($q) => $q->ordenadas()])
            ->get();

        return view('livewire.planificador.cinturones', [
            'escalas' => EscalaGrado::cases(),
            'escalaActual' => $escala,
            'grados' => $grados,
        ]);
    }
}
