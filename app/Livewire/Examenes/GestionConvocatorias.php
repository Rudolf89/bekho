<?php

namespace App\Livewire\Examenes;

use App\Models\Convocatoria;
use App\Models\Sede;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Exámenes de grado')]
class GestionConvocatorias extends Component
{
    public string $nombre = '';

    public ?int $sede_id = null;

    public ?string $fecha = null;

    public bool $mostrarModal = false;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'sede_id' => ['nullable', Rule::exists('sedes', 'id')],
            'fecha' => ['required', 'date'],
        ];
    }

    public function nueva(): void
    {
        $this->reset('nombre', 'sede_id', 'fecha');
        $this->fecha = now()->format('Y-m-d');
        $this->resetErrorBag();
        $this->mostrarModal = true;
    }

    public function guardar(): void
    {
        $datos = $this->validate();

        Convocatoria::create($datos);

        Flux::toast(variant: 'success', text: 'Convocatoria creada.');
        $this->mostrarModal = false;
    }

    public function render()
    {
        return view('livewire.examenes.gestion-convocatorias', [
            'convocatorias' => Convocatoria::with('sede')
                ->withCount('inscripciones')
                ->latest('fecha')->get(),
            'sedes' => Sede::orderBy('nombre')->get(),
        ]);
    }
}
