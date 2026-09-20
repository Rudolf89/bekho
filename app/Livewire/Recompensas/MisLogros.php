<?php

namespace App\Livewire\Recompensas;

use App\Enums\TipoRecompensa;
use App\Models\Matricula;
use App\Models\Recompensa;
use App\Models\Tutela;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Colección de logros del alumno/apoderado: cada matrícula (la propia del alumno
 * o las de los hijos que tutela) con sus recompensas ganadas y los coleccionables
 * que aún le faltan. Solo lectura.
 */
#[Title('Mis logros')]
class MisLogros extends Component
{
    public function render()
    {
        $usuario = Auth::user();

        // Personas cuyas matrículas puede ver: la propia + las que tutela.
        $personaIds = collect([$usuario->persona_id])->filter()
            ->merge(Tutela::query()->where('apoderado_persona_id', $usuario->persona_id)->pluck('alumno_persona_id'))
            ->unique()
            ->all();

        // Los logros se leen SIN el scope de grupo: la colección es de la persona a
        // lo largo de todas sus matrículas (incluidas las de grupos anteriores tras
        // un traslado), y el apoderado puede tutelar hijos en distintos grupos.
        $matriculas = Matricula::withoutGlobalScopes()
            ->whereIn('persona_id', $personaIds)
            ->with(['persona', 'logros' => fn ($q) => $q->withoutGlobalScopes()])
            ->get();

        $catalogo = Recompensa::activas()->ordenadas()->get();

        $coleccion = $matriculas->map(function (Matricula $matricula) use ($catalogo) {
            $ganados = $matricula->logros->groupBy('recompensa_id')->map->count();

            $aplicables = $catalogo
                ->filter(fn (Recompensa $r) => $r->grupo_etario === null || $r->grupo_etario === $matricula->grupo_etario)
                ->groupBy(fn (Recompensa $r) => $r->tipo->value);

            return [
                'matricula' => $matricula,
                'ganados' => $ganados,
                'recompensas' => $aplicables,
                'total' => (int) $matricula->logros->count(),
            ];
        });

        return view('livewire.recompensas.mis-logros', [
            'coleccion' => $coleccion,
            'tipos' => TipoRecompensa::cases(),
        ]);
    }
}
