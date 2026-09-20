<?php

namespace App\Livewire\Programas\Admin;

use App\Enums\TipoContenido;
use App\Livewire\Concerns\SoloLectura;
use App\Models\Contenido;
use App\Models\EtapaPrograma;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Administración de los contenidos de una etapa.
 */
#[Title('Administrar contenidos')]
class AdminContenidos extends Component
{
    use SoloLectura;

    public EtapaPrograma $etapa;

    public ?int $editandoId = null;

    public string $titulo = '';

    public ?string $descripcion = null;

    public string $tipo = 'texto';

    public ?string $cuerpo = null;

    public ?string $url_recurso = null;

    public int $orden = 0;

    public bool $activo = true;

    public bool $mostrarModal = false;

    public function mount(EtapaPrograma $etapa): void
    {
        $this->etapa = $etapa;
    }

    /**
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

    public function nuevo(): void
    {
        $this->reset('editandoId', 'titulo', 'descripcion', 'cuerpo', 'url_recurso', 'activo');
        $this->tipo = 'texto';
        $this->orden = (int) ($this->etapa->contenidos()->max('orden') ?? -1) + 1;
        $this->resetErrorBag();
        $this->mostrarModal = true;
    }

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

    public function guardar(): void
    {
        $this->bloqueaSiSoloLectura();

        $datos = $this->validate();

        if ($datos['tipo'] === 'texto') {
            $datos['url_recurso'] = null;
        } else {
            $datos['cuerpo'] = null;
        }

        if ($this->editandoId) {
            Contenido::findOrFail($this->editandoId)->update($datos);
            Flux::toast(variant: 'success', text: 'Contenido actualizado.');
        } else {
            $this->etapa->contenidos()->create($datos);
            Flux::toast(variant: 'success', text: 'Contenido creado.');
        }

        $this->mostrarModal = false;
    }

    public function alternarActivo(Contenido $contenido): void
    {
        $this->bloqueaSiSoloLectura();

        $contenido->update(['activo' => ! $contenido->activo]);
    }

    public function render()
    {
        return view('livewire.programas.admin.admin-contenidos', [
            'contenidos' => $this->etapa->contenidos()->get(),
        ]);
    }
}
