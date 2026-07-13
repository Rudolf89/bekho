<?php

namespace App\Livewire\Planillas;

use App\Enums\HabilidadVida;
use App\Models\Planilla;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Editar planilla')]
class EditarPlanilla extends Component
{
    public Planilla $planilla;

    public ?string $habilidad_vida = null;

    /** @var array<int, string|null> contenido por id de bloque */
    public array $contenidos = [];

    /** @var array<int, string|null> nota por id de cuadrante */
    public array $notas = [];

    public function mount(Planilla $planilla): void
    {
        $this->planilla = $planilla;
        $this->habilidad_vida = $planilla->habilidad_vida?->value;
        $this->contenidos = $planilla->bloques->pluck('contenido', 'id')->all();
        $this->notas = $planilla->cuadrantes->pluck('nota', 'id')->all();
    }

    public function guardar(): void
    {
        $this->validate([
            'habilidad_vida' => ['nullable', Rule::enum(HabilidadVida::class)],
            'contenidos.*' => ['nullable', 'string'],
            'notas.*' => ['nullable', 'string'],
        ]);

        $this->planilla->update(['habilidad_vida' => $this->habilidad_vida]);

        foreach ($this->planilla->bloques as $bloque) {
            $bloque->update(['contenido' => $this->contenidos[$bloque->id] ?? null]);
        }

        foreach ($this->planilla->cuadrantes as $cuadrante) {
            $cuadrante->update(['nota' => $this->notas[$cuadrante->id] ?? null]);
        }

        Flux::toast(variant: 'success', text: 'Planilla guardada.');
    }

    public function render()
    {
        // Recarga con relaciones frescas para la vista.
        $this->planilla->load(['bloques', 'cuadrantes', 'programa']);

        return view('livewire.planillas.editar-planilla', [
            'habilidades' => HabilidadVida::cases(),
        ]);
    }
}
