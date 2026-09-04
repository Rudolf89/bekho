<?php

namespace App\Livewire\Examenes;

use App\Livewire\Concerns\ConTabla;
use App\Models\User;
use App\Services\ServicioExamenes;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Conteo de graduaciones')]
class ConteoCascada extends Component
{
    use ConTabla;

    public function render(ServicioExamenes $servicio)
    {
        $instructores = $this->aplicarBusqueda(User::role(['direccion', 'instructor']), ['name'])
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => [
                'nombre' => $u->name,
                'conteo' => $servicio->conteoEnCascada($u),
                'collar' => $servicio->collarDe($u),
            ])
            ->sortByDesc('conteo')
            ->values();

        return view('livewire.examenes.conteo-cascada', [
            'instructores' => $instructores,
            'totalGraduaciones' => $instructores->sum('conteo'),
            'umbralesConfigurados' => collect(config('bekho.premios_collar', []))->filter()->isNotEmpty(),
        ]);
    }
}
