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
     * Marca como completado y navega al siguiente contenido del nivel (o vuelve
     * al nivel si era el último).
     */
    public function completarYSeguir(ServicioFormacion $servicio)
    {
        $servicio->marcarContenido(Auth::user(), $this->contenido, EstadoProgreso::Completado);

        $siguiente = $this->siguiente();

        return $this->redirect(
            $siguiente
                ? route('formacion.contenido', $siguiente)
                : route('formacion.nivel', $this->contenido->nivel_id),
            navigate: true,
        );
    }

    /**
     * Contenido siguiente del mismo nivel (por orden), o null si es el último.
     */
    public function siguiente(): ?Contenido
    {
        return $this->vecino(1);
    }

    /**
     * Contenido anterior del mismo nivel (por orden), o null si es el primero.
     */
    public function anterior(): ?Contenido
    {
        return $this->vecino(-1);
    }

    /**
     * Vecino del contenido actual dentro del nivel (delta -1 anterior, +1 siguiente).
     */
    private function vecino(int $delta): ?Contenido
    {
        $hermanos = $this->contenido->nivel->contenidos()->where('activo', true)->get();
        $indice = $hermanos->search(fn (Contenido $c) => $c->id === $this->contenido->id);

        return $indice === false ? null : $hermanos->get($indice + $delta);
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
        $hermanos = $this->contenido->nivel->contenidos()->where('activo', true)->get();
        $indice = $hermanos->search(fn (Contenido $c) => $c->id === $this->contenido->id);
        $indice = $indice === false ? 0 : $indice;

        return view('livewire.formacion.ver-contenido', [
            'urlIncrustada' => $this->urlIncrustada(),
            'anterior' => $hermanos->get($indice - 1),
            'siguiente' => $hermanos->get($indice + 1),
            'indice' => $indice,
            'total' => $hermanos->count(),
        ]);
    }
}
