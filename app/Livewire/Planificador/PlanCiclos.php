<?php

namespace App\Livewire\Planificador;

use App\Enums\FilaPlannerCiclo;
use App\Models\Ciclo;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Class planners de los 6 ciclos (grillas del Manual Legacy): para el ciclo
 * elegido muestra su grilla (fila × bloque de semanas) y la lección de vida.
 */
#[Title('Ciclos')]
class PlanCiclos extends Component
{
    #[Url]
    public ?int $cicloId = null;

    public function mount(): void
    {
        $this->cicloId ??= Ciclo::ordenados()->value('id');
    }

    public function render()
    {
        $ciclos = Ciclo::ordenados()->get();

        $ciclo = $ciclos->firstWhere('id', $this->cicloId) ?? $ciclos->first();

        $ciclo?->load(['planner', 'lecciones']);

        $celdas = $ciclo
            ? $ciclo->planner->groupBy([
                fn ($p) => $p->fila->value,
                fn ($p) => $p->bloque,
            ])
            : collect();

        return view('livewire.planificador.plan-ciclos', [
            'ciclos' => $ciclos,
            'ciclo' => $ciclo,
            'filas' => FilaPlannerCiclo::cases(),
            'bloques' => FilaPlannerCiclo::bloques(),
            'celdas' => $celdas,
        ]);
    }
}
