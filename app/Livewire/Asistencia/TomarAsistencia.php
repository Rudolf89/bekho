<?php

namespace App\Livewire\Asistencia;

use App\Enums\EstadoAsistencia;
use App\Models\Asistencia;
use App\Models\Clase;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Tomar asistencia')]
class TomarAsistencia extends Component
{
    public ?string $claseId = '';

    public string $fecha = '';

    public function mount(): void
    {
        $this->fecha = now()->format('Y-m-d');
    }

    /**
     * Marca (o cambia) el estado de un estudiante en la clase y fecha actuales.
     * Persiste de inmediato para agilizar la toma en el tatami.
     */
    public function marcar(int $estudianteId, string $estado): void
    {
        $clase = $this->claseSeleccionada();

        if (! $clase) {
            return;
        }

        // El estudiante debe pertenecer al roster esperado de la clase.
        if (! $clase->estudiantesEsperados()->whereKey($estudianteId)->exists()) {
            return;
        }

        $estadoEnum = EstadoAsistencia::from($estado);

        Asistencia::updateOrCreate(
            [
                'clase_id' => $clase->id,
                'estudiante_id' => $estudianteId,
                'fecha' => $this->fecha,
            ],
            [
                'academia_id' => $clase->academia_id,
                'estado' => $estadoEnum,
                'registrado_por' => Auth::id(),
            ],
        );
    }

    /**
     * Clase seleccionada (respeta el scope por academia).
     */
    protected function claseSeleccionada(): ?Clase
    {
        return $this->claseId ? Clase::find($this->claseId) : null;
    }

    public function render()
    {
        $clase = $this->claseSeleccionada();

        $roster = $clase ? $clase->estudiantesEsperados()->get() : collect();

        // Mapa estudiante_id => estado, para la clase y fecha actuales.
        $estados = [];
        if ($clase) {
            $estados = Asistencia::where('clase_id', $clase->id)
                ->whereDate('fecha', $this->fecha)
                ->pluck('estado', 'estudiante_id')
                ->map(fn ($e) => $e instanceof EstadoAsistencia ? $e->value : $e)
                ->all();
        }

        $presentes = collect($estados)->filter(fn ($e) => $e === EstadoAsistencia::Presente->value)->count();

        return view('livewire.asistencia.tomar-asistencia', [
            'clases' => Clase::activas()->with('sede')->orderBy('dia_semana')->orderBy('hora_inicio')->get(),
            'clase' => $clase,
            'roster' => $roster,
            'estados' => $estados,
            'presentes' => $presentes,
        ]);
    }
}
