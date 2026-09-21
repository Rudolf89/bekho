<?php

namespace App\Livewire\Competencia;

use App\Enums\EstadoPlanillaCompetencia;
use App\Enums\PapelJuezPlanilla;
use App\Livewire\Concerns\SoloLectura;
use App\Models\CompetidorPlanilla;
use App\Models\CriterioPrueba;
use App\Models\JuezPlanilla;
use App\Models\PlanillaCompetencia;
use App\Services\ServicioPlanillaCompetencia;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Detalle operativo de una planilla de competencia: competidores, jueces y la
 * captura de puntajes por criterio (con el ranking calculado).
 */
#[Title('Planilla de competencia')]
class VerPlanillaCompetencia extends Component
{
    use SoloLectura;

    public PlanillaCompetencia $planilla;

    // Alta de competidor
    public string $compNombre = '';

    public ?int $compEdad = null;

    public ?string $compPais = null;

    // Alta de juez
    public string $juezPapel = 'central';

    public string $juezNombre = '';

    public ?string $juezNivel = null;

    /**
     * Puntajes en edición: [competidor_id][criterio_id] => dígito (0–9).
     *
     * @var array<int, array<int, string>>
     */
    public array $puntajes = [];

    public function mount(PlanillaCompetencia $planilla): void
    {
        $this->planilla = $planilla;
        $this->hidratarPuntajes();
    }

    protected function hidratarPuntajes(): void
    {
        $this->puntajes = [];

        foreach ($this->planilla->competidores()->with('puntajes')->get() as $competidor) {
            foreach ($competidor->puntajes as $p) {
                $this->puntajes[$competidor->id][$p->criterio_prueba_id] = (string) $p->digito();
            }
        }
    }

    public function agregarCompetidor(): void
    {
        $this->bloqueaSiSoloLectura();
        $this->validate(['compNombre' => ['required', 'string', 'max:120'], 'compEdad' => ['nullable', 'integer', 'min:3', 'max:99'], 'compPais' => ['nullable', 'string', 'max:60']]);

        CompetidorPlanilla::create([
            'planilla_id' => $this->planilla->id,
            'orden' => $this->planilla->competidores()->count() + 1,
            'nombre' => $this->compNombre,
            'edad' => $this->compEdad,
            'pais' => $this->compPais,
        ]);

        $this->reset('compNombre', 'compEdad', 'compPais');
        Flux::toast(variant: 'success', text: 'Competidor agregado.');
    }

    public function eliminarCompetidor(int $id): void
    {
        $this->bloqueaSiSoloLectura();
        CompetidorPlanilla::where('planilla_id', $this->planilla->id)->whereKey($id)->delete();
        $this->hidratarPuntajes();
    }

    public function agregarJuez(): void
    {
        $this->bloqueaSiSoloLectura();
        $this->validate([
            'juezPapel' => ['required', Rule::enum(PapelJuezPlanilla::class)],
            'juezNombre' => ['required', 'string', 'max:120'],
            'juezNivel' => ['nullable', 'string', 'max:60'],
        ]);

        JuezPlanilla::create([
            'planilla_id' => $this->planilla->id,
            'papel' => $this->juezPapel,
            'nombre' => $this->juezNombre,
            'nivel_pais' => $this->juezNivel,
        ]);

        $this->reset('juezNombre', 'juezNivel');
        Flux::toast(variant: 'success', text: 'Juez agregado.');
    }

    public function eliminarJuez(int $id): void
    {
        $this->bloqueaSiSoloLectura();
        JuezPlanilla::where('planilla_id', $this->planilla->id)->whereKey($id)->delete();
    }

    /**
     * Guarda el puntaje de un competidor en un criterio: el usuario escribe el
     * dígito (1–9 → 9.1–9.9) o 0 como penalización si el criterio lo permite.
     */
    public function guardarPuntaje(int $competidorId, int $criterioId, ServicioPlanillaCompetencia $servicio): void
    {
        $this->bloqueaSiSoloLectura();

        $competidor = CompetidorPlanilla::where('planilla_id', $this->planilla->id)->findOrFail($competidorId);
        $criterio = CriterioPrueba::where('prueba_id', $this->planilla->prueba_id)->findOrFail($criterioId);

        $digito = (int) ($this->puntajes[$competidorId][$criterioId] ?? '');
        $puntaje = $digito === 0 ? 0 : 90 + $digito;

        try {
            $servicio->registrarPuntaje($competidor, $criterio, $puntaje);
        } catch (ValidationException $e) {
            $this->addError("puntajes.{$competidorId}.{$criterioId}", $e->getMessage());
        }
    }

    public function cerrar(): void
    {
        $this->bloqueaSiSoloLectura();
        $this->planilla->update(['estado' => EstadoPlanillaCompetencia::Cerrada->value]);
        Flux::toast(variant: 'success', text: 'Planilla cerrada.');
    }

    public function reabrir(): void
    {
        $this->bloqueaSiSoloLectura();
        $this->planilla->update(['estado' => EstadoPlanillaCompetencia::Borrador->value]);
    }

    public function render(ServicioPlanillaCompetencia $servicio): View
    {
        $this->planilla->load(['prueba.criterios.escala', 'jueces', 'grupoEdad', 'categoria']);

        return view('livewire.competencia.ver-planilla-competencia', [
            'criterios' => $this->planilla->prueba->criterios,
            'ranking' => $servicio->ranking($this->planilla),
            'papeles' => PapelJuezPlanilla::cases(),
        ]);
    }
}
