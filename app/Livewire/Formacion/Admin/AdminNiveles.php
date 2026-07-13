<?php

namespace App\Livewire\Formacion\Admin;

use App\Models\Nivel;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Administrar niveles')]
class AdminNiveles extends Component
{
    /** Id del nivel en edición (null = creando). */
    public ?int $editandoId = null;

    public string $nombre = '';

    public ?string $descripcion = null;

    public int $orden = 0;

    public bool $activo = true;

    public bool $mostrarModal = false;

    /**
     * Reglas de validación del formulario.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'orden' => ['required', 'integer', 'min:0'],
            'activo' => ['boolean'],
        ];
    }

    /**
     * Abre el modal para crear un nivel nuevo.
     */
    public function nuevo(): void
    {
        $this->reset('editandoId', 'nombre', 'descripcion', 'activo');
        $this->orden = (int) (Nivel::max('orden') ?? -1) + 1;
        $this->resetErrorBag();
        $this->mostrarModal = true;
    }

    /**
     * Abre el modal para editar un nivel existente.
     */
    public function editar(Nivel $nivel): void
    {
        $this->editandoId = $nivel->id;
        $this->nombre = $nivel->nombre;
        $this->descripcion = $nivel->descripcion;
        $this->orden = $nivel->orden;
        $this->activo = $nivel->activo;
        $this->resetErrorBag();
        $this->mostrarModal = true;
    }

    /**
     * Guarda (crea o actualiza) el nivel.
     */
    public function guardar(): void
    {
        $datos = $this->validate();

        if ($this->editandoId) {
            Nivel::findOrFail($this->editandoId)->update($datos);
            Flux::toast(variant: 'success', text: 'Nivel actualizado.');
        } else {
            Nivel::create($datos);
            Flux::toast(variant: 'success', text: 'Nivel creado.');
        }

        $this->mostrarModal = false;
    }

    /**
     * Activa o desactiva un nivel.
     */
    public function alternarActivo(Nivel $nivel): void
    {
        $nivel->update(['activo' => ! $nivel->activo]);
    }

    /**
     * Sube un nivel una posición (intercambia orden con el anterior).
     */
    public function subir(Nivel $nivel): void
    {
        $anterior = Nivel::ordenados()
            ->where('orden', '<', $nivel->orden)
            ->orderByDesc('orden')
            ->first();

        $this->intercambiar($nivel, $anterior);
    }

    /**
     * Baja un nivel una posición (intercambia orden con el siguiente).
     */
    public function bajar(Nivel $nivel): void
    {
        $siguiente = Nivel::ordenados()
            ->where('orden', '>', $nivel->orden)
            ->orderBy('orden')
            ->first();

        $this->intercambiar($nivel, $siguiente);
    }

    /**
     * Intercambia el orden de dos niveles.
     */
    protected function intercambiar(Nivel $a, ?Nivel $b): void
    {
        if (! $b) {
            return;
        }

        $ordenA = $a->orden;
        $a->update(['orden' => $b->orden]);
        $b->update(['orden' => $ordenA]);
    }

    public function render()
    {
        return view('livewire.formacion.admin.admin-niveles', [
            'niveles' => Nivel::ordenados()->withCount('contenidos')->get(),
        ]);
    }
}
