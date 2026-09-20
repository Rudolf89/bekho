<?php

namespace App\Livewire\Competencia;

use App\Livewire\Concerns\SoloLectura;
use App\Models\CategoriaCompetencia;
use App\Models\GrupoEdad;
use App\Models\PlanillaCompetencia;
use App\Models\Prueba;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Listado y creación de planillas de competencia (certificación de planillero).
 * Catálogo transversal de la federación (sin grupo_id).
 */
#[Title('Competencia')]
class PlanillasCompetencia extends Component
{
    use SoloLectura, WithPagination;

    public ?string $prueba_id = '';

    public ?string $grupo_edad_id = '';

    public ?string $categoria_competencia_id = '';

    public ?string $fecha = null;

    public ?string $genero = null;

    public ?string $nro_pista = null;

    public bool $mostrarModal = false;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'prueba_id' => ['required', Rule::exists('pruebas', 'id')],
            'grupo_edad_id' => ['nullable', Rule::exists('grupos_edad', 'id')],
            'categoria_competencia_id' => ['nullable', Rule::exists('categorias_competencia', 'id')],
            'fecha' => ['nullable', 'date'],
            'genero' => ['nullable', 'string', 'max:30'],
            'nro_pista' => ['nullable', 'string', 'max:30'],
        ];
    }

    public function nueva(): void
    {
        $this->bloqueaSiSoloLectura();
        $this->reset('prueba_id', 'grupo_edad_id', 'categoria_competencia_id', 'fecha', 'genero', 'nro_pista');
        $this->resetErrorBag();
        $this->mostrarModal = true;
    }

    public function crear()
    {
        $this->bloqueaSiSoloLectura();

        $datos = $this->validate();
        $datos['grupo_edad_id'] = $datos['grupo_edad_id'] ?: null;
        $datos['categoria_competencia_id'] = $datos['categoria_competencia_id'] ?: null;

        $planilla = PlanillaCompetencia::create([
            ...$datos,
            'estado' => 'borrador',
            'creado_por_user_id' => Auth::id(),
        ]);

        Flux::toast(variant: 'success', text: 'Planilla creada.');

        return $this->redirectRoute('competencia.ver', $planilla, navigate: true);
    }

    public function render()
    {
        return view('livewire.competencia.planillas-competencia', [
            'planillas' => PlanillaCompetencia::with(['prueba', 'grupoEdad', 'categoria'])
                ->withCount('competidores')
                ->latest('id')
                ->paginate(15),
            'pruebas' => Prueba::ordenados()->get(),
            'gruposEdad' => GrupoEdad::orderBy('orden')->get(),
            'categorias' => CategoriaCompetencia::orderBy('orden')->get(),
        ]);
    }
}
