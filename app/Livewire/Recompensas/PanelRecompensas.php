<?php

namespace App\Livewire\Recompensas;

use App\Enums\TipoRecompensa;
use App\Models\Estudiante;
use App\Models\Logro;
use App\Models\Recompensa;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Panel de recompensas (gamificación): el instructor elige un alumno y otorga o
 * quita logros. El catálogo aplicable depende del grupo etario del alumno (Star
 * Tag es de Tigers, Franjas de For Kids; los coleccionables, de todos).
 */
#[Title('Recompensas')]
class PanelRecompensas extends Component
{
    #[Url]
    public ?int $estudianteId = null;

    public function updatedEstudianteId(): void
    {
        $this->resetErrorBag();
    }

    /**
     * Alumno seleccionado, si es visible para el usuario.
     */
    private function estudiante(): ?Estudiante
    {
        if (! $this->estudianteId) {
            return null;
        }

        return Estudiante::visiblePara(Auth::user())->find($this->estudianteId);
    }

    public function otorgar(int $recompensaId): void
    {
        abort_unless(Auth::user()->can('gestionar recompensas'), 403);

        $estudiante = $this->estudiante();
        $recompensa = Recompensa::activas()->find($recompensaId);
        if (! $estudiante || ! $recompensa) {
            return;
        }

        // Las no repetibles se ganan una sola vez.
        if (! $recompensa->repetible && $estudiante->logros()->where('recompensa_id', $recompensa->id)->exists()) {
            return;
        }

        $estudiante->logros()->create([
            'recompensa_id' => $recompensa->id,
            'otorgado_por' => Auth::id(),
            'otorgado_at' => now(),
        ]);

        Flux::toast(variant: 'success', text: "Se otorgó “{$recompensa->nombre}” a {$estudiante->nombre}.");
    }

    public function quitar(int $recompensaId): void
    {
        abort_unless(Auth::user()->can('gestionar recompensas'), 403);

        $estudiante = $this->estudiante();
        if (! $estudiante) {
            return;
        }

        // Quita el último logro de esa recompensa (para Star Tag, resta uno).
        $logro = $estudiante->logros()
            ->where('recompensa_id', $recompensaId)
            ->latest('id')
            ->first();

        $logro?->delete();
    }

    public function render()
    {
        $estudiante = $this->estudiante();

        $recompensas = Recompensa::activas()
            ->when($estudiante, fn ($q) => $q->paraGrupo($estudiante->grupo_etario))
            ->ordenadas()
            ->get()
            ->groupBy(fn (Recompensa $r) => $r->tipo->value);

        // Conteo de logros del alumno por recompensa.
        $conteo = $estudiante
            ? $estudiante->logros()
                ->selectRaw('recompensa_id, count(*) as total')
                ->groupBy('recompensa_id')
                ->pluck('total', 'recompensa_id')
            : collect();

        return view('livewire.recompensas.panel-recompensas', [
            'estudiantes' => Estudiante::visiblePara(Auth::user())->activos()->orderBy('nombre')->get(),
            'estudiante' => $estudiante,
            'recompensasPorTipo' => $recompensas,
            'tipos' => TipoRecompensa::cases(),
            'conteo' => $conteo,
        ]);
    }
}
