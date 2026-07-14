<?php

namespace App\Livewire\Academias;

use App\Livewire\Concerns\ConOrden;
use App\Models\Academia;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Academias')]
class GestionAcademias extends Component
{
    use ConOrden;

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

    public function editar(Academia $academia): void
    {
        $this->editandoId = $academia->id;
        $this->nombre = $academia->nombre;
        $this->email = $academia->email;
        $this->telefono = $academia->telefono;
        $this->logo = $academia->logo;
        $this->activo = $academia->activo;
        $this->resetErrorBag();
        $this->mostrarModal = true;
    }

    public function guardar(): void
    {
        $datos = $this->validate();

        if ($this->editandoId) {
            Academia::findOrFail($this->editandoId)->update($datos);
            Flux::toast(variant: 'success', text: 'Academia actualizada.');
        } else {
            Academia::create($datos);
            Flux::toast(variant: 'success', text: 'Academia creada.');
        }

        $this->mostrarModal = false;
    }

    public function alternarActivo(Academia $academia): void
    {
        $academia->update(['activo' => ! $academia->activo]);
    }

    public function render()
    {
        return view('livewire.academias.gestion-academias', [
            'academias' => $this->aplicarOrden(Academia::withCount(['sedes', 'usuarios']), ['nombre', 'activo'], 'nombre')->get(),
        ]);
    }
}
