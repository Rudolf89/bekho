<?php

namespace App\Livewire\Examenes;

use App\Livewire\Concerns\ConTabla;
use App\Models\DistintivoRango;
use App\Models\User;
use App\Services\ServicioCreditos;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Créditos de graduación')]
class ConteoCascada extends Component
{
    use ConTabla;

    public function render(ServicioCreditos $creditos): View
    {
        // Perfil del usuario en sesión (rango, distintivo, total, cadena).
        $persona = auth()->user()?->persona;
        $perfil = $persona ? $creditos->perfilDe($persona) : null;

        // Instructores (cuentas con persona): su distintivo se calcula por los
        // créditos de graduación acumulados de su persona.
        $instructores = $this->aplicarBusqueda(User::role(['direccion', 'instructor']), ['name'])
            ->whereNotNull('persona_id')
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => [
                'nombre' => $u->name,
                'conteo' => $creditos->totalCreditos($u->persona),
                'collar' => $creditos->distintivoDe($u->persona)?->nombre,
            ])
            ->sortByDesc('conteo')
            ->values();

        return view('livewire.examenes.conteo-cascada', [
            'perfil' => $perfil,
            'instructores' => $instructores,
            'totalGraduaciones' => $instructores->sum('conteo'),
            'umbralesConfigurados' => DistintivoRango::whereNotNull('graduados_requeridos')->exists(),
        ]);
    }
}
