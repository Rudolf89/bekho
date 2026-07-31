<?php

namespace App\Livewire\Cuestionarios;

use App\Enums\EstadoIntento;
use App\Models\Cuestionario;
use App\Models\IntentoCuestionario;
use App\Notifications\IntentoDecidido;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Panel del examinador (permiso "gestionar cuestionarios"): resultados de los
 * intentos de los alumnos y la decisión de aprobar o pedir volver a intentar.
 * Aprobar un intento que no alcanzó el umbral exige justificación.
 */
#[Title('Resultados de cuestionarios')]
class ResultadosCuestionarios extends Component
{
    use WithPagination;

    #[Url]
    public string $cuestionarioId = '';

    #[Url]
    public string $estado = '';

    /** Intento en revisión (fila abierta). */
    public ?int $revisandoId = null;

    public string $justificacion = '';

    public function updating(): void
    {
        $this->resetPage();
    }

    public function abrirRevision(int $id): void
    {
        $this->revisandoId = $id;
        $this->justificacion = '';
        $this->resetValidation();
    }

    public function cerrarRevision(): void
    {
        $this->reset(['revisandoId', 'justificacion']);
        $this->resetValidation();
    }

    public function aprobar(int $id): void
    {
        $this->decidir($id, EstadoIntento::Aprobado);
    }

    public function reintentar(int $id): void
    {
        $this->decidir($id, EstadoIntento::Reintentar);
    }

    private function decidir(int $id, EstadoIntento $decision): void
    {
        abort_unless(Auth::user()->can('gestionar cuestionarios'), 403);

        $intento = IntentoCuestionario::findOrFail($id);

        // Aprobar por debajo del umbral es una excepción: exige justificación.
        if ($decision === EstadoIntento::Aprobado && $intento->requiereJustificacion() && trim($this->justificacion) === '') {
            $this->addError('justificacion', 'Aprobar un intento que no alcanzó el mínimo exige justificar la decisión.');

            return;
        }

        $intento->update([
            'estado' => $decision,
            'revisado_por' => Auth::id(),
            'revisado_at' => now(),
            'justificacion' => trim($this->justificacion) !== '' ? trim($this->justificacion) : null,
        ]);

        // Avisar al alumno de la decisión (notificación en la app).
        $intento->user?->notify(new IntentoDecidido($intento));

        $this->cerrarRevision();

        Flux::toast(variant: 'success', text: $decision === EstadoIntento::Aprobado
            ? 'Intento aprobado.'
            : 'Se pidió volver a intentar.');
    }

    public function render()
    {
        $intentos = IntentoCuestionario::query()
            ->with(['user', 'cuestionario', 'revisor'])
            ->when($this->cuestionarioId !== '', fn ($q) => $q->where('cuestionario_id', $this->cuestionarioId))
            ->when($this->estado !== '', fn ($q) => $q->where('estado', $this->estado))
            ->latest('finalizado_at')
            ->paginate(15);

        return view('livewire.cuestionarios.resultados-cuestionarios', [
            'intentos' => $intentos,
            'cuestionarios' => Cuestionario::ordenados()->get(),
            'estados' => EstadoIntento::cases(),
            'pendientes' => IntentoCuestionario::pendientes()->count(),
        ]);
    }
}
