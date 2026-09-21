<?php

namespace App\Livewire\Programas;

use App\Enums\EstadoProgreso;
use App\Enums\TipoContenido;
use App\Models\Contenido;
use App\Services\ServicioProgramas;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Lectura de un contenido de estudio, con navegación entre capítulos de la etapa
 * y progreso por persona.
 */
#[Title('Contenido de estudio')]
class VerContenido extends Component
{
    public Contenido $contenido;

    public EstadoProgreso $estado;

    public function mount(Contenido $contenido, ServicioProgramas $servicio): void
    {
        abort_unless($contenido->activo, 404);

        $this->contenido = $contenido;
        $persona = Auth::user()?->persona;
        $this->estado = $persona ? $servicio->progresoDe($persona, $contenido) : EstadoProgreso::Pendiente;

        if ($persona && $this->estado === EstadoProgreso::Pendiente) {
            $servicio->marcarContenido($persona, $contenido, EstadoProgreso::Visto);
            $this->estado = EstadoProgreso::Visto;
        }
    }

    public function completar(ServicioProgramas $servicio): void
    {
        if ($persona = Auth::user()?->persona) {
            $servicio->marcarContenido($persona, $this->contenido, EstadoProgreso::Completado);
            $this->estado = EstadoProgreso::Completado;
            Flux::toast(variant: 'success', text: 'Contenido marcado como completado.');
        }
    }

    public function completarYSeguir(ServicioProgramas $servicio): void
    {
        if ($persona = Auth::user()?->persona) {
            $servicio->marcarContenido($persona, $this->contenido, EstadoProgreso::Completado);
        }

        $siguiente = $this->vecino(1);

        $this->redirect(
            $siguiente
                ? route('programas.contenido', $siguiente)
                : route('programas.programa', $this->contenido->etapaPrograma->programa_id),
            navigate: true,
        );
    }

    /**
     * Vecino del contenido actual dentro de la etapa (delta -1 anterior, +1 siguiente).
     */
    private function vecino(int $delta): ?Contenido
    {
        $hermanos = $this->hermanos();
        $indice = $hermanos->search(fn (Contenido $c) => $c->id === $this->contenido->id);

        return $indice === false ? null : $hermanos->get($indice + $delta);
    }

    /**
     * @return Collection<int, Contenido>
     */
    private function hermanos()
    {
        return $this->contenido->etapaPrograma->contenidos()->where('activo', true)->get();
    }

    /**
     * Convierte una URL de video conocida (YouTube/Vimeo) en URL para iframe.
     */
    public function urlIncrustada(): ?string
    {
        if ($this->contenido->tipo !== TipoContenido::Video || ! $this->contenido->url_recurso) {
            return null;
        }

        $url = $this->contenido->url_recurso;

        if (preg_match('~youtube\.com/watch\?v=([\w-]+)~', $url, $m) || preg_match('~youtu\.be/([\w-]+)~', $url, $m)) {
            return "https://www.youtube.com/embed/{$m[1]}";
        }

        if (preg_match('~vimeo\.com/(\d+)~', $url, $m)) {
            return "https://player.vimeo.com/video/{$m[1]}";
        }

        return null;
    }

    public function render(): View
    {
        $hermanos = $this->hermanos();
        $indice = $hermanos->search(fn (Contenido $c) => $c->id === $this->contenido->id);
        $indice = $indice === false ? 0 : $indice;

        return view('livewire.programas.ver-contenido', [
            'urlIncrustada' => $this->urlIncrustada(),
            'anterior' => $hermanos->get($indice - 1),
            'siguiente' => $hermanos->get($indice + 1),
            'indice' => $indice,
            'total' => $hermanos->count(),
        ]);
    }
}
