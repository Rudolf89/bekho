<?php

namespace App\Livewire\Planificador;

use App\Enums\Cuadrante;
use App\Enums\RolCuadrante;
use App\Models\CuadranteItem;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Cuadrantes de Enseñanza (marco pedagógico ATA): referencia de las
 * responsabilidades del alumno y del instructor en cada cuadrante.
 */
#[Title('Cuadrantes de Enseñanza')]
class CuadrantesEnsenanza extends Component
{
    public function render(): View
    {
        $items = CuadranteItem::orderBy('orden')->get();

        return view('livewire.planificador.cuadrantes-ensenanza', [
            'cuadrantes' => Cuadrante::cases(),
            'roles' => RolCuadrante::cases(),
            'porCuadranteRol' => $items->groupBy([
                fn (CuadranteItem $it) => $it->cuadrante->value,
                fn (CuadranteItem $it) => $it->rol->value,
            ]),
        ]);
    }
}
