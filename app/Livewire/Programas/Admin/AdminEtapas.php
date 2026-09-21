<?php

namespace App\Livewire\Programas\Admin;

use App\Livewire\Concerns\SoloLectura;
use App\Models\EtapaPrograma;
use App\Models\Programa;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Administración de las etapas de los programas (catálogo de la federación).
 */
#[Title('Administrar etapas')]
class AdminEtapas extends Component
{
    use SoloLectura;

    public ?int $editandoId = null;

    public string $programa_id = '';

    public string $nombre = '';

    public ?string $descripcion = null;

    public int $orden = 0;

    public ?int $horas_requeridas = null;

    public ?int $edad_minima = null;

    public bool $activo = true;

    public bool $mostrarModal = false;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'programa_id' => ['required', 'exists:programas,id'],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'orden' => ['required', 'integer', 'min:0'],
            'horas_requeridas' => ['nullable', 'integer', 'min:0'],
            'edad_minima' => ['nullable', 'integer', 'min:0', 'max:99'],
            'activo' => ['boolean'],
        ];
    }

    public function nuevo(): void
    {
        $this->reset('editandoId', 'programa_id', 'nombre', 'descripcion', 'horas_requeridas', 'edad_minima', 'activo');
        $this->orden = (int) (EtapaPrograma::max('orden') ?? -1) + 1;
        $this->resetErrorBag();
        $this->mostrarModal = true;
    }

    public function editar(EtapaPrograma $etapa): void
    {
        $this->editandoId = $etapa->id;
        $this->programa_id = (string) $etapa->programa_id;
        $this->nombre = $etapa->nombre;
        $this->descripcion = $etapa->descripcion;
        $this->orden = $etapa->orden;
        $this->horas_requeridas = $etapa->horas_requeridas;
        $this->edad_minima = $etapa->edad_minima;
        $this->activo = $etapa->activo;
        $this->resetErrorBag();
        $this->mostrarModal = true;
    }

    public function guardar(): void
    {
        $this->bloqueaSiSoloLectura();

        $datos = $this->validate();

        if ($this->editandoId) {
            EtapaPrograma::findOrFail($this->editandoId)->update($datos);
            Flux::toast(variant: 'success', text: 'Etapa actualizada.');
        } else {
            EtapaPrograma::create($datos);
            Flux::toast(variant: 'success', text: 'Etapa creada.');
        }

        $this->mostrarModal = false;
    }

    public function alternarActivo(EtapaPrograma $etapa): void
    {
        $this->bloqueaSiSoloLectura();

        $etapa->update(['activo' => ! $etapa->activo]);
    }

    public function render(): View
    {
        return view('livewire.programas.admin.admin-etapas', [
            'etapas' => EtapaPrograma::with('programa')->orderBy('programa_id')->orderBy('orden')->withCount('contenidos')->get(),
            'programas' => Programa::ordenados()->get(),
        ]);
    }
}
