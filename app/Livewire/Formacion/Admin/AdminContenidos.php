<?php

namespace App\Livewire\Formacion\Admin;

use App\Enums\TipoContenido;
use App\Models\Contenido;
use App\Models\Nivel;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Administrar contenidos')]
class AdminContenidos extends Component
{
    public Nivel $nivel;

    /** Id del contenido en edición (null = creando). */
    public ?int $editandoId = null;

    public string $titulo = '';

    public ?string $descripcion = null;

    public string $tipo = 'texto';

    public ?string $cuerpo = null;

    public ?string $url_recurso = null;

    public int $orden = 0;

    public bool $activo = true;

    public bool $mostrarModal = false;

    /**
     * Monta el componente con el nivel resuelto por la ruta.
     */
    public function mount(Nivel $nivel): void
    {
        $this->nivel = $nivel;
    }

    /**
     * Reglas de validación. La URL es obligatoria para video/documento y el
     * cuerpo para texto.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'tipo' => ['required', 'in:texto,video,documento'],
            'cuerpo' => ['nullable', 'string', 'required_if:tipo,texto'],
            'url_recurso' => ['nullable', 'url', 'required_if:tipo,video,documento'],
            'orden' => ['required', 'integer', 'min:0'],
            'activo' => ['boolean'],
        ];
    }

    /**
     * Etiquetas de tipo para el selector.
     *
     * @return array<string, string>
     */
    public function tiposDisponibles(): array
    {
        $tipos = [];

        foreach (TipoContenido::cases() as $caso) {
            $tipos[$caso->value] = $caso->etiqueta();
        }

        return $tipos;
    }

    /**
     * Abre el modal para crear un contenido nuevo.
     */
    public function nuevo(): void
    {
        $this->reset('editandoId', 'titulo', 'descripcion', 'cuerpo', 'url_recurso', 'activo');
        $this->tipo = 'texto';
        $this->orden = (int) ($this->nivel->contenidos()->max('orden') ?? -1) + 1;
        $this->resetErrorBag();
        $this->mostrarModal = true;
    }

    /**
     * Abre el modal para editar un contenido existente.
     */
    public function editar(Contenido $contenido): void
    {
        $this->editandoId = $contenido->id;
        $this->titulo = $contenido->titulo;
        $this->descripcion = $contenido->descripcion;
        $this->tipo = $contenido->tipo->value;
        $this->cuerpo = $contenido->cuerpo;
        $this->url_recurso = $contenido->url_recurso;
        $this->orden = $contenido->orden;
        $this->activo = $contenido->activo;
        $this->resetErrorBag();
        $this->mostrarModal = true;
    }

    /**
     * Guarda (crea o actualiza) el contenido dentro del nivel.
     */
    public function guardar(): void
    {
        $datos = $this->validate();

        // Limpia el campo que no corresponde al tipo elegido.
        if ($datos['tipo'] === 'texto') {
            $datos['url_recurso'] = null;
        } else {
            $datos['cuerpo'] = null;
        }

        if ($this->editandoId) {
            Contenido::findOrFail($this->editandoId)->update($datos);
            Flux::toast(variant: 'success', text: 'Contenido actualizado.');
        } else {
            $this->nivel->contenidos()->create($datos);
            Flux::toast(variant: 'success', text: 'Contenido creado.');
        }

        $this->mostrarModal = false;
    }

    /**
     * Activa o desactiva un contenido.
     */
    public function alternarActivo(Contenido $contenido): void
    {
        $contenido->update(['activo' => ! $contenido->activo]);
    }

    /**
     * Sube un contenido una posición dentro del nivel.
     */
    public function subir(Contenido $contenido): void
    {
        $anterior = $this->nivel->contenidos()
            ->where('orden', '<', $contenido->orden)
            ->orderByDesc('orden')
            ->first();

        $this->intercambiar($contenido, $anterior);
    }

    /**
     * Baja un contenido una posición dentro del nivel.
     */
    public function bajar(Contenido $contenido): void
    {
        $siguiente = $this->nivel->contenidos()
            ->where('orden', '>', $contenido->orden)
            ->orderBy('orden')
            ->first();

        $this->intercambiar($contenido, $siguiente);
    }

    /**
     * Intercambia el orden de dos contenidos.
     */
    protected function intercambiar(Contenido $a, ?Contenido $b): void
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
        return view('livewire.formacion.admin.admin-contenidos', [
            'contenidos' => $this->nivel->contenidos()->get(),
        ]);
    }
}
