<?php

namespace App\Livewire\Recompensas;

use App\Enums\TipoRecompensa;
use App\Models\Estudiante;
use App\Models\Recompensa;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Colección de logros del alumno/apoderado: cada alumno (los hijos del apoderado
 * o la propia ficha del alumno) con sus recompensas ganadas y los coleccionables
 * que aún le faltan. Solo lectura.
 */
#[Title('Mis logros')]
class MisLogros extends Component
{
    public function render()
    {
        $usuario = Auth::user();

        // Hijos del apoderado + ficha propia del alumno (sin duplicar).
        $estudiantes = $usuario->hijos()->with('logros')->get()
            ->merge(Estudiante::where('user_id', $usuario->id)->with('logros')->get())
            ->unique('id')
            ->values();

        $catalogo = Recompensa::activas()->ordenadas()->get();

        $coleccion = $estudiantes->map(function (Estudiante $estudiante) use ($catalogo) {
            // Cuántas veces ganó cada recompensa.
            $ganados = $estudiante->logros->groupBy('recompensa_id')->map->count();

            // Catálogo aplicable a su grupo etario (coleccionables aplican a todos).
            $aplicables = $catalogo
                ->filter(fn (Recompensa $r) => $r->grupo_etario === null || $r->grupo_etario === $estudiante->grupo_etario)
                ->groupBy(fn (Recompensa $r) => $r->tipo->value);

            return [
                'estudiante' => $estudiante,
                'ganados' => $ganados,
                'recompensas' => $aplicables,
                'total' => (int) $estudiante->logros->count(),
            ];
        });

        return view('livewire.recompensas.mis-logros', [
            'coleccion' => $coleccion,
            'tipos' => TipoRecompensa::cases(),
        ]);
    }
}
