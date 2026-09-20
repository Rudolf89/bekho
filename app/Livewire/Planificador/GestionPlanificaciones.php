<?php

namespace App\Livewire\Planificador;

use App\Enums\GrupoEtario;
use App\Enums\HabilidadVida;
use App\Enums\NivelEntrenamiento;
use App\Livewire\Concerns\ConTabla;
use App\Livewire\Concerns\SoloLectura;
use App\Models\PlanificacionClase;
use App\Models\Programa;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Planificaciones de clase')]
class GestionPlanificaciones extends Component
{
    use ConTabla, SoloLectura;

    public string $nombre = '';

    public string $grupo_etario = '';

    public string $nivel = '';

    public ?string $programa_id = '';

    public ?string $habilidad_vida = '';

    public bool $mostrarModal = false;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'grupo_etario' => ['required', Rule::enum(GrupoEtario::class)],
            'nivel' => ['required', Rule::enum(NivelEntrenamiento::class)],
            'programa_id' => ['nullable', Rule::exists('programas', 'id')],
            'habilidad_vida' => ['nullable', Rule::enum(HabilidadVida::class)],
        ];
    }

    public function nueva(): void
    {
        $this->reset('nombre', 'grupo_etario', 'nivel', 'programa_id', 'habilidad_vida');
        $this->resetErrorBag();
        $this->mostrarModal = true;
    }

    public function guardar()
    {
        $this->bloqueaSiSoloLectura();

        // Los <select> opcionales devuelven '' cuando no se elige nada.
        $this->programa_id = $this->programa_id ?: null;
        $this->habilidad_vida = $this->habilidad_vida ?: null;

        $datos = $this->validate();

        $planificacion = PlanificacionClase::create($datos);
        $planificacion->generarEstructura();

        Flux::toast(variant: 'success', text: 'Planificación creada. Completa su contenido.');

        return $this->redirect(route('planificaciones.editar', $planificacion), navigate: true);
    }

    public function render()
    {
        return view('livewire.planificador.gestion-planificaciones', [
            'planificaciones' => $this->aplicarOrden(
                $this->aplicarBusqueda(PlanificacionClase::with('programa'), ['nombre', 'programa.nombre']),
                ['nombre', 'grupo_etario', 'nivel'], 'grupo_etario'
            )->orderBy('nombre')->get(),
            'grupos' => GrupoEtario::cases(),
            'niveles' => NivelEntrenamiento::cases(),
            'programas' => Programa::activos()->ordenados()->get(),
            'habilidades' => HabilidadVida::cases(),
        ]);
    }
}
