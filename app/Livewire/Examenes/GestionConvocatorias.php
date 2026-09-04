<?php

namespace App\Livewire\Examenes;

use App\Livewire\Concerns\ConTabla;
use App\Livewire\Concerns\SoloLectura;
use App\Models\Convocatoria;
use App\Models\Sede;
use Flux\Flux;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Exámenes de grado')]
class GestionConvocatorias extends Component
{
    use AuthorizesRequests, ConTabla, SoloLectura;

    public string $nombre = '';

    public ?string $sede_id = '';

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
        // Crear convocatorias es gestión; el instructor solo inscribe.
        $this->authorize('gestionar examenes');
        $this->bloqueaSiSoloLectura();

        $this->reset('nombre', 'sede_id', 'fecha');
        $this->fecha = now()->format('Y-m-d');
        $this->resetErrorBag();
        $this->mostrarModal = true;
    }

    public function guardar(): void
    {
        $this->authorize('gestionar examenes');
        $this->bloqueaSiSoloLectura();

        $this->sede_id = $this->sede_id ?: null;

        $datos = $this->validate();

        Convocatoria::create($datos);

        Flux::toast(variant: 'success', text: 'Convocatoria creada.');
        $this->mostrarModal = false;
    }

    public function render()
    {
        $convocatorias = $this->aplicarOrden(
            $this->aplicarBusqueda(
                Convocatoria::with('sede')->withCount('inscripciones'),
                ['nombre', 'sede.nombre'],
            ),
            ['fecha', 'nombre', 'estado'],
            'fecha',
        )->get();

        return view('livewire.examenes.gestion-convocatorias', [
            'convocatorias' => $convocatorias,
            'totalInscritos' => $convocatorias->sum('inscripciones_count'),
            'sedes' => Sede::orderBy('nombre')->get(),
        ]);
    }
}
