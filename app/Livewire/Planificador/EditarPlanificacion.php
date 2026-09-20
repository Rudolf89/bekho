<?php

namespace App\Livewire\Planificador;

use App\Enums\HabilidadVida;
use App\Livewire\Concerns\SoloLectura;
use App\Models\PlanificacionClase;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Editar planificación')]
class EditarPlanificacion extends Component
{
    use SoloLectura;

    public PlanificacionClase $planificacion;

    public ?string $habilidad_vida = '';

    /** @var array<int, string|null> contenido por id de bloque */
    public array $contenidos = [];

    /** @var array<int, string|null> nota por id de cuadrante */
    public array $notas = [];

    public function mount(PlanificacionClase $planificacion): void
    {
        $this->planificacion = $planificacion;
        $this->habilidad_vida = $planificacion->habilidad_vida?->value ?? '';
        $this->contenidos = $planificacion->bloques->pluck('contenido', 'id')->all();
        $this->notas = $planificacion->cuadrantes->pluck('nota', 'id')->all();
    }

    public function guardar(): void
    {
        $this->bloqueaSiSoloLectura();

        $this->habilidad_vida = $this->habilidad_vida ?: null;

        $this->validate([
            'habilidad_vida' => ['nullable', Rule::enum(HabilidadVida::class)],
            'contenidos.*' => ['nullable', 'string'],
            'notas.*' => ['nullable', 'string'],
        ]);

        $this->planificacion->update(['habilidad_vida' => $this->habilidad_vida]);

        foreach ($this->planificacion->bloques as $bloque) {
            $bloque->update(['contenido' => $this->contenidos[$bloque->id] ?? null]);
        }

        foreach ($this->planificacion->cuadrantes as $cuadrante) {
            $cuadrante->update(['nota' => $this->notas[$cuadrante->id] ?? null]);
        }

        Flux::toast(variant: 'success', text: 'Planificación guardada.');
    }

    public function render()
    {
        // Recarga con relaciones frescas para la vista.
        $this->planificacion->load(['bloques', 'cuadrantes', 'programa']);

        return view('livewire.planificador.editar-planificacion', [
            'habilidades' => HabilidadVida::cases(),
        ]);
    }
}
