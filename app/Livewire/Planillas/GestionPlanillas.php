<?php

namespace App\Livewire\Planillas;

use App\Enums\GrupoEtario;
use App\Enums\HabilidadVida;
use App\Enums\NivelEntrenamiento;
use App\Models\Planilla;
use App\Models\Programa;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Planillas de clase')]
class GestionPlanillas extends Component
{
    public string $nombre = '';

    public string $grupo_etario = '';

    public string $nivel = '';

    public ?int $programa_id = null;

    public ?string $habilidad_vida = null;

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
        $datos = $this->validate();

        $planilla = Planilla::create($datos);
        $planilla->generarEstructura();

        Flux::toast(variant: 'success', text: 'Planilla creada. Completa su contenido.');

        return $this->redirect(route('planillas.editar', $planilla), navigate: true);
    }

    public function render()
    {
        return view('livewire.planillas.gestion-planillas', [
            'planillas' => Planilla::with('programa')
                ->orderBy('grupo_etario')->orderBy('nivel')->orderBy('nombre')->get(),
            'grupos' => GrupoEtario::cases(),
            'niveles' => NivelEntrenamiento::cases(),
            'programas' => Programa::activos()->ordenados()->get(),
            'habilidades' => HabilidadVida::cases(),
        ]);
    }
}
