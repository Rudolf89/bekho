<?php

namespace App\Livewire\Grupos;

use App\Livewire\Concerns\ConTabla;
use App\Models\Grupo;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Grupos')]
class GestionGrupos extends Component
{
    use ConTabla;

    public ?int $editandoId = null;

    public string $nombre = '';

    public ?string $email = null;

    public ?string $telefono = null;

    public ?string $logo = null;

    public bool $activo = true;

    public bool $mostrarModal = false;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'logo' => ['nullable', 'string', 'max:255'],
            'activo' => ['boolean'],
        ];
    }

    public function nueva(): void
    {
        $this->reset('editandoId', 'nombre', 'email', 'telefono', 'logo');
        $this->activo = true;
        $this->resetErrorBag();
        $this->mostrarModal = true;
    }

    public function editar(Grupo $grupo): void
    {
        $this->editandoId = $grupo->id;
        $this->nombre = $grupo->nombre;
        $this->email = $grupo->email;
        $this->telefono = $grupo->telefono;
        $this->logo = $grupo->logo;
        $this->activo = $grupo->activo;
        $this->resetErrorBag();
        $this->mostrarModal = true;
    }

    public function guardar(): void
    {
        $datos = $this->validate();

        if ($this->editandoId) {
            Grupo::findOrFail($this->editandoId)->update($datos);
            Flux::toast(variant: 'success', text: 'Grupo actualizada.');
        } else {
            Grupo::create($datos);
            Flux::toast(variant: 'success', text: 'Grupo creada.');
        }

        $this->mostrarModal = false;
    }

    public function alternarActivo(Grupo $grupo): void
    {
        $grupo->update(['activo' => ! $grupo->activo]);
    }

    public function render()
    {
        // El admin-plataforma no filtra lecturas, así que los conteos de sedes y
        // usuarios salen globales (el total real de cada grupo).
        return view('livewire.grupos.gestion-grupos', [
            'grupos' => $this->aplicarOrden(
                $this->aplicarBusqueda(Grupo::withCount(['sedes', 'usuarios']), ['nombre', 'email', 'telefono']),
                ['nombre', 'activo'], 'nombre'
            )->get(),
        ]);
    }
}
