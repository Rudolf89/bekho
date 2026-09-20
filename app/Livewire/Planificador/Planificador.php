<?php

namespace App\Livewire\Planificador;

use App\Enums\FilaPlannerCiclo;
use App\Enums\GrupoEtario;
use App\Enums\NivelEntrenamiento;
use App\Livewire\Concerns\SoloLectura;
use App\Models\CategoriaCalentamiento;
use App\Models\Ciclo;
use App\Models\Clase;
use App\Models\EjercicioCalentamiento;
use App\Models\LeccionVida;
use App\Models\NotaCalentamiento;
use App\Models\PlanificacionCinturonNegro;
use App\Models\PlanificacionClase;
use App\Models\PlannerCiclo;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Planificador Unificado: en una sola vista, la rutina de la clase (planificación)
 * por grupo × nivel (o el planificador de Cinturón Negro por semanas), el armado
 * de calentamiento y la Lección de Vida.
 *
 * Es week-aware: el plan de clase (bloques × cuadrante) es la estructura fija,
 * y encima se muestra la ROTACIÓN del ciclo elegido (qué cinturón/forma/cuadrante
 * toca este bloque de semanas), reutilizando el class planner del ciclo
 * (planner_ciclo) en vez de duplicarlo.
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

    /** Ciclo (Habilidad para la Vida) elegido para ver qué contenido rota. */
    #[Url]
    public ?int $cicloId = null;

    /** Bloque de semanas del ciclo (1&2 · 3&4 · 5&6 · 7&8). */
    #[Url]
    public string $bloque = '1&2';

    public int $lecSemana = 7;

    /** Clase (del horario del grupo activo) a la que se guarda el calentamiento. */
    public string $claseId = '';

    /**
     * Ejercicios elegidos en "Armar calentamiento" (ids como strings).
     *
     * @var array<int, string>
     */
    public array $seleccion = [];

    public function mount(): void
    {
        $this->lecSemana = (int) (LeccionVida::min('semana') ?? 7);
        $this->cicloId ??= Ciclo::ordenados()->value('id');
    }

    /**
     * Rotación del ciclo elegido para el bloque de semanas actual: qué contenido
     * (Warm-Up/Kicks/Forms/Quadrants/Protech/Drills) toca esta semana, indexado
     * por fila. Es la capa que hace "cambiar semana a semana" el planificador,
     * reutilizando el class planner del ciclo (planner_ciclo).
     *
     * @return array<string, PlannerCiclo>
     */
    protected function rotacionDelCiclo(): array
    {
        if (! $this->cicloId) {
            return [];
        }

        return PlannerCiclo::where('ciclo_id', $this->cicloId)
            ->where('bloque', $this->bloque)
            ->get()
            ->keyBy(fn (PlannerCiclo $p) => $p->fila->value)
            ->all();
    }

    /**
     * Al elegir la clase, el catálogo se ajusta a su grupo etario y se carga la
     * rutina de calentamiento ya guardada para esa clase.
     */
    public function updatedClaseId(): void
    {
        $clase = $this->claseParaCalentamiento();

        if ($clase) {
            $this->grupo = $clase->grupo_etario->value;
        }

        $this->cargarSeleccion();
    }

    /**
     * Al cambiar de ciclo, apunta la lección a la primera semana disponible de
     * ese ciclo (así el tab Lección no queda en vacío por una semana inexistente).
     */
    public function updatedCicloId(): void
    {
        $primera = LeccionVida::where('ciclo_id', $this->cicloId)->min('semana');

        if ($primera !== null) {
            $this->lecSemana = (int) $primera;
        }
    }

    protected function esBlackBelt(): bool
    {
        return $this->nivel === 'blackbelt';
    }

    /**
     * Planificación transversal (grupo × nivel), si el nivel no es Cinturón Negro.
     */
    protected function planificacionActual(): ?PlanificacionClase
    {
        if ($this->esBlackBelt()) {
            return null;
        }

        return PlanificacionClase::where('grupo_etario', $this->grupo)
            ->where('nivel', $this->nivel)
            ->first();
    }

    /**
     * Clase del horario (grupo activo) elegida para guardar el calentamiento.
     */
    protected function claseParaCalentamiento(): ?Clase
    {
        return $this->claseId ? Clase::find($this->claseId) : null;
    }

    /**
     * Carga la rutina de calentamiento guardada en la clase elegida.
     */
    public function cargarSeleccion(): void
    {
        $clase = $this->claseParaCalentamiento();

        $this->seleccion = $clase
            ? $clase->calentamiento()->pluck('ejercicios_calentamiento.id')->map(fn ($id) => (string) $id)->all()
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

        $clase = $this->claseParaCalentamiento();

        if (! $clase) {
            Flux::toast(variant: 'warning', text: 'Elige una clase para guardar el calentamiento.');

            return;
        }

        $sync = [];
        foreach (array_values($this->seleccion) as $i => $id) {
            $sync[(int) $id] = ['orden' => $i + 1];
        }
        $clase->calentamiento()->sync($sync);

        Flux::toast(variant: 'success', text: 'Rutina de calentamiento guardada en la clase.');
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
            // Aviso: el contenido mostrado no está validado por la federación.
            'avisoSinVerificar' => false,
        ];

        if ($this->tab === 'planner' && ! $this->esBlackBelt()) {
            $planificacion = $this->planificacionActual()?->load(['bloques', 'curriculo']);
            $datos['planificacion'] = $planificacion;
            $datos['curriculo'] = $planificacion?->curriculo;
            $datos['avisoSinVerificar'] = $planificacion !== null && ! $planificacion->verificado;

            // Capa de rotación: qué toca esta semana según el ciclo elegido.
            $ciclos = Ciclo::ordenados()->get();
            $datos['ciclos'] = $ciclos;
            $datos['cicloActual'] = $ciclos->firstWhere('id', $this->cicloId) ?? $ciclos->first();
            $datos['filasCiclo'] = FilaPlannerCiclo::cases();
            $datos['bloquesCiclo'] = FilaPlannerCiclo::bloques();
            $datos['bloqueActual'] = $this->bloque;
            $datos['rotacion'] = $this->rotacionDelCiclo();
        }

        if ($this->tab === 'planner' && $this->esBlackBelt()) {
            $datos['semanasBB'] = PlanificacionCinturonNegro::ordenadas()->get();
            $datos['bb'] = PlanificacionCinturonNegro::where('clave', $this->bbSemana)
                ->with(['secciones', 'adaptaciones'])
                ->first();
            $datos['avisoSinVerificar'] = $datos['bb'] !== null && ! $datos['bb']->verificado;
        }

        if ($this->tab === 'warmup') {
            $datos['categorias'] = CategoriaCalentamiento::paraGrupo($this->grupo)->ordenadas()->with('ejercicios')->get();
            $datos['avisoSinVerificar'] = $datos['categorias']->contains(fn ($c) => ! $c->verificado);
            $datos['nota'] = NotaCalentamiento::where('grupo_etario', $this->grupo)->first();
            $datos['clases'] = Clase::activas()->orderBy('nombre')->get();

            $porId = EjercicioCalentamiento::with('categoria')
                ->whereIn('id', $this->seleccion)
                ->get()
                ->keyBy('id');
            $datos['seleccionados'] = collect($this->seleccion)
                ->map(fn ($id) => $porId->get((int) $id))
                ->filter()
                ->values();
            $datos['puedeGuardar'] = $this->claseId !== '';
        }

        if ($this->tab === 'leccion') {
            // La lección se acota al ciclo elegido: el mismo eje que la rotación.
            $ciclos = Ciclo::ordenados()->get();
            $cicloActual = $ciclos->firstWhere('id', $this->cicloId) ?? $ciclos->first();

            $lecciones = $cicloActual
                ? $cicloActual->lecciones()->with('ciclo')->get()
                : collect();

            $datos['ciclos'] = $ciclos;
            $datos['cicloActual'] = $cicloActual;
            $datos['lecciones'] = $lecciones;
            // Todas las semanas del ciclo (8) se muestran como casilleros: las
            // cargadas resaltadas, las pendientes atenuadas.
            $datos['semanasCiclo'] = $cicloActual?->semanas ?? 8;
            $datos['semanasCargadas'] = $lecciones->pluck('semana')->all();
            $datos['leccion'] = $lecciones->firstWhere('semana', $this->lecSemana);
        }

        return view('livewire.planificador.planificador', $datos);
    }
}
