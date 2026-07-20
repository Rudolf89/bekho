<?php

namespace App\Livewire\Planillas;

use App\Enums\GrupoEtario;
use App\Enums\NivelEntrenamiento;
use App\Livewire\Concerns\SoloLectura;
use App\Models\CategoriaCalentamiento;
use App\Models\EjercicioCalentamiento;
use App\Models\LeccionVida;
use App\Models\NotaCalentamiento;
use App\Models\PlanificacionCinturonNegro;
use App\Models\Planilla;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Planificador Unificado: en una sola vista, la rutina de la clase (planilla)
 * por grupo × nivel (o el planificador de Cinturón Negro por semanas), el armado
 * de calentamiento y la Lección de Vida.
 */
#[Title('Planificador')]
class Planificador extends Component
{
    use SoloLectura;

    #[Url]
    public string $tab = 'planner';

    // grupo y nivel quedan en la URL: así una clase puede enlazar a su planilla.
    #[Url]
    public string $grupo = 'for_kids';

    /** Nivel de entrenamiento, o 'blackbelt' para el planificador de Cinturón Negro. */
    #[Url]
    public string $nivel = 'principiantes';

    public string $bbSemana = 's12';

    public int $lecSemana = 7;

    /**
     * Ejercicios elegidos en "Armar calentamiento" (ids como strings).
     *
     * @var array<int, string>
     */
    public array $seleccion = [];

    public function mount(): void
    {
        $this->lecSemana = (int) (LeccionVida::min('semana') ?? 7);
        $this->cargarSeleccion();
    }

    public function updatedGrupo(): void
    {
        $this->cargarSeleccion();
    }

    public function updatedNivel(): void
    {
        $this->cargarSeleccion();
    }

    protected function esBlackBelt(): bool
    {
        return $this->nivel === 'blackbelt';
    }

    /**
     * Planilla (grupo × nivel) de la academia activa, si el nivel no es Cinturón Negro.
     */
    protected function planillaActual(): ?Planilla
    {
        if ($this->esBlackBelt()) {
            return null;
        }

        return Planilla::where('grupo_etario', $this->grupo)
            ->where('nivel', $this->nivel)
            ->first();
    }

    /**
     * Carga la rutina de calentamiento guardada en la planilla actual.
     */
    public function cargarSeleccion(): void
    {
        $planilla = $this->planillaActual();

        $this->seleccion = $planilla
            ? $planilla->calentamiento()->pluck('ejercicios_calentamiento.id')->map(fn ($id) => (string) $id)->all()
            : [];
    }

    public function alternarEjercicio(int $id): void
    {
        $clave = (string) $id;

        $this->seleccion = in_array($clave, $this->seleccion, true)
            ? array_values(array_diff($this->seleccion, [$clave]))
            : [...$this->seleccion, $clave];
    }

    public function limpiarCalentamiento(): void
    {
        $this->seleccion = [];
    }

    public function guardarCalentamiento(): void
    {
        $this->bloqueaSiSoloLectura();

        $planilla = $this->planillaActual();

        if (! $planilla) {
            Flux::toast(variant: 'warning', text: 'Elige un grupo y un nivel (no Cinturón Negro) con planilla para guardar.');

            return;
        }

        $sync = [];
        foreach (array_values($this->seleccion) as $i => $id) {
            $sync[(int) $id] = ['orden' => $i + 1];
        }
        $planilla->calentamiento()->sync($sync);

        Flux::toast(variant: 'success', text: 'Rutina de calentamiento guardada en la planilla.');
    }

    /**
     * Niveles del selector: los tres con currículo + Cinturón Negro.
     *
     * @return list<array{valor: string, etiqueta: string}>
     */
    public function nivelesDisponibles(): array
    {
        return [
            ['valor' => NivelEntrenamiento::Principiantes->value, 'etiqueta' => NivelEntrenamiento::Principiantes->etiqueta()],
            ['valor' => NivelEntrenamiento::Intermedio->value, 'etiqueta' => NivelEntrenamiento::Intermedio->etiqueta()],
            ['valor' => NivelEntrenamiento::Avanzado->value, 'etiqueta' => NivelEntrenamiento::Avanzado->etiqueta()],
            ['valor' => 'blackbelt', 'etiqueta' => 'Cinturón Negro'],
        ];
    }

    public function render()
    {
        $datos = [
            'grupos' => GrupoEtario::cases(),
            'niveles' => $this->nivelesDisponibles(),
            'esBlackBelt' => $this->esBlackBelt(),
            'grupoEnum' => GrupoEtario::from($this->grupo),
        ];

        if ($this->tab === 'planner' && ! $this->esBlackBelt()) {
            $planilla = $this->planillaActual()?->load(['bloques', 'curriculo']);
            $datos['planilla'] = $planilla;
            $datos['curriculo'] = $planilla?->curriculo;
        }

        if ($this->tab === 'planner' && $this->esBlackBelt()) {
            $datos['semanasBB'] = PlanificacionCinturonNegro::ordenadas()->get();
            $datos['bb'] = PlanificacionCinturonNegro::where('clave', $this->bbSemana)
                ->with(['secciones', 'adaptaciones'])
                ->first();
        }

        if ($this->tab === 'warmup') {
            $datos['categorias'] = CategoriaCalentamiento::paraGrupo($this->grupo)->ordenadas()->with('ejercicios')->get();
            $datos['nota'] = NotaCalentamiento::where('grupo_etario', $this->grupo)->first();

            $porId = EjercicioCalentamiento::with('categoria')
                ->whereIn('id', $this->seleccion)
                ->get()
                ->keyBy('id');
            $datos['seleccionados'] = collect($this->seleccion)
                ->map(fn ($id) => $porId->get((int) $id))
                ->filter()
                ->values();
            $datos['puedeGuardar'] = $this->planillaActual() !== null;
        }

        if ($this->tab === 'leccion') {
            $datos['lecciones'] = LeccionVida::orderBy('semana')->get();
            $datos['leccion'] = LeccionVida::where('semana', $this->lecSemana)->first();
        }

        return view('livewire.planillas.planificador', $datos);
    }
}
