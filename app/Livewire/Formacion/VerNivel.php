<?php

namespace App\Livewire\Formacion;

use App\Models\Nivel;
use App\Services\ServicioFormacion;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Nivel de formación')]
class VerNivel extends Component
{
    public Nivel $nivel;

    /**
     * Monta el componente con el nivel resuelto por la ruta.
     */
    public function mount(Nivel $nivel): void
    {
        abort_unless($nivel->activo, 404);

        $this->nivel = $nivel;
    }

    /**
     * Renderiza el detalle del nivel con sus contenidos y estados.
     */
    public function render(ServicioFormacion $servicio)
    {
        $usuario = Auth::user();

        $contenidos = $this->nivel->contenidos()
            ->where('activo', true)
            ->get()
            ->map(function ($contenido) use ($usuario): array {
                return [
                    'modelo' => $contenido,
                    'estado' => $usuario->progresoEn($contenido),
                ];
            });

        return view('livewire.formacion.ver-nivel', [
            'contenidos' => $contenidos,
            'avance' => $servicio->avanceDeNivel($usuario, $this->nivel),
        ]);
    }
}
