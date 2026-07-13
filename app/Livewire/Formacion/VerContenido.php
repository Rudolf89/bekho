<?php

namespace App\Livewire\Formacion;

use App\Enums\EstadoProgreso;
use App\Enums\TipoContenido;
use App\Models\Contenido;
use App\Services\ServicioFormacion;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Contenido de formación')]
class VerContenido extends Component
{
    public Contenido $contenido;

    public EstadoProgreso $estado;

    /**
     * Monta el componente con el contenido resuelto por la ruta y marca
     * automáticamente como "visto" si aún estaba pendiente.
     */
    public function mount(Contenido $contenido, ServicioFormacion $servicio): void
    {
        abort_unless($contenido->activo, 404);

        $this->contenido = $contenido;
        $this->estado = Auth::user()->progresoEn($contenido);

        if ($this->estado === EstadoProgreso::Pendiente) {
            $servicio->marcarContenido(Auth::user(), $contenido, EstadoProgreso::Visto);
            $this->estado = EstadoProgreso::Visto;
        }
    }

    /**
     * Marca el contenido como completado.
     */
    public function completar(ServicioFormacion $servicio): void
    {
        $servicio->marcarContenido(Auth::user(), $this->contenido, EstadoProgreso::Completado);
        $this->estado = EstadoProgreso::Completado;

        Flux::toast(variant: 'success', text: 'Contenido marcado como completado.');
    }

    /**
     * Convierte una URL de video conocida (YouTube/Vimeo) en URL para iframe.
     * Devuelve null si no se reconoce (se mostrará como enlace).
     */
    public function urlIncrustada(): ?string
    {
        if ($this->contenido->tipo !== TipoContenido::Video || ! $this->contenido->url_recurso) {
            return null;
        }

        $url = $this->contenido->url_recurso;

        if (preg_match('~youtube\.com/watch\?v=([\w-]+)~', $url, $m)) {
            return "https://www.youtube.com/embed/{$m[1]}";
        }

        if (preg_match('~youtu\.be/([\w-]+)~', $url, $m)) {
            return "https://www.youtube.com/embed/{$m[1]}";
        }

        if (preg_match('~vimeo\.com/(\d+)~', $url, $m)) {
            return "https://player.vimeo.com/video/{$m[1]}";
        }

        return null;
    }

    public function render()
    {
        return view('livewire.formacion.ver-contenido', [
            'urlIncrustada' => $this->urlIncrustada(),
        ]);
    }
}
