<?php

namespace App\Livewire\Recompensas;

use App\Enums\TipoRecompensa;
use App\Models\Matricula;
use App\Models\Recompensa;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Panel de recompensas (gamificación): el instructor elige una matrícula (alumno)
 * y otorga o quita logros. El catálogo aplicable depende del grupo etario del
 * alumno (Star Tag es de Tigers, Franjas de For Kids; los coleccionables, de todos).
 */
#[Title('Recompensas')]
class PanelRecompensas extends Component
{
    #[Url]
    public ?int $matriculaId = null;

    public function updatedMatriculaId(): void
    {
        $this->resetErrorBag();
    }

    /**
     * Matrícula seleccionada, si es visible para el usuario.
     */
    private function matricula(): ?Matricula
    {
        if (! $this->matriculaId) {
            return null;
        }

        return Matricula::visiblePara(Auth::user())->with('persona')->find($this->matriculaId);
    }

    public function otorgar(int $recompensaId): void
    {
        abort_unless(Auth::user()->can('gestionar recompensas'), 403);

        $matricula = $this->matricula();
        $recompensa = Recompensa::activas()->find($recompensaId);
        if (! $matricula || ! $recompensa) {
            return;
        }

        // Las no repetibles se ganan una sola vez.
        if (! $recompensa->repetible && $matricula->logros()->where('recompensa_id', $recompensa->id)->exists()) {
            return;
        }

        $matricula->logros()->create([
            'grupo_id' => $matricula->grupo_id,
            'recompensa_id' => $recompensa->id,
            'otorgado_por' => Auth::id(),
            'otorgado_at' => now(),
        ]);

        Flux::toast(variant: 'success', text: "Se otorgó “{$recompensa->nombre}” a {$matricula->persona?->nombreCompleto()}.");
    }

    public function quitar(int $recompensaId): void
    {
        abort_unless(Auth::user()->can('gestionar recompensas'), 403);

        $matricula = $this->matricula();
        if (! $matricula) {
            return;
        }

        // Quita el último logro de esa recompensa (para Star Tag, resta uno).
        $logro = $matricula->logros()
            ->where('recompensa_id', $recompensaId)
            ->latest('id')
            ->first();

        $logro?->delete();
    }

    public function render()
    {
        $matricula = $this->matricula();

        $recompensas = Recompensa::activas()
            ->when($matricula, fn ($q) => $q->paraGrupo($matricula->grupo_etario))
            ->ordenadas()
            ->get()
            ->groupBy(fn (Recompensa $r) => $r->tipo->value);

        // Conteo de logros de la matrícula por recompensa.
        $conteo = $matricula
            ? $matricula->logros()
                ->selectRaw('recompensa_id, count(*) as total')
                ->groupBy('recompensa_id')
                ->pluck('total', 'recompensa_id')
            : collect();

        return view('livewire.recompensas.panel-recompensas', [
            'matriculas' => Matricula::visiblePara(Auth::user())->activas()->with('persona')->get()
                ->sortBy(fn (Matricula $m) => $m->persona?->nombreCompleto())->values(),
            'matricula' => $matricula,
            'recompensasPorTipo' => $recompensas,
            'tipos' => TipoRecompensa::cases(),
            'conteo' => $conteo,
        ]);
    }
}
