<?php

namespace App\Livewire\Estudiantes;

use App\Enums\EscalaGrado;
use App\Enums\GrupoEtario;
use App\Enums\NivelEntrenamiento;
use App\Livewire\Concerns\ConOrden;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Programa;
use App\Models\Sede;
use App\Models\User;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Estudiantes')]
class GestionEstudiantes extends Component
{
    use ConOrden, WithPagination;

    // Filtros
    public string $buscar = '';

    public string $filtroGrupo = '';

    public string $filtroNivel = '';

    public string $filtroEstado = 'activos';

    // Formulario
    public ?int $editandoId = null;

    public string $nombre = '';

    public ?string $rut = null;

    public ?string $fecha_nacimiento = null;

    public string $grupo_etario = '';

    public string $nivel = '';

    public ?string $grado_id = '';

    public ?string $sede_id = '';

    public ?string $telefono_contacto = null;

    public ?string $email_contacto = null;

    public bool $activo = true;

    /** @var array<int, int> */
    public array $programas = [];

    /** @var array<int, int> */
    public array $apoderados = [];

    public bool $mostrarModal = false;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'rut' => ['nullable', 'string', 'max:20'],
            'fecha_nacimiento' => ['nullable', 'date'],
            'grupo_etario' => ['required', Rule::enum(GrupoEtario::class)],
            'nivel' => ['required', Rule::enum(NivelEntrenamiento::class)],
            'grado_id' => ['nullable', Rule::exists('grados', 'id')],
            'sede_id' => ['nullable', Rule::exists('sedes', 'id')],
            'telefono_contacto' => ['nullable', 'string', 'max:50'],
            'email_contacto' => ['nullable', 'email', 'max:255'],
            'activo' => ['boolean'],
            'programas' => ['array'],
            'programas.*' => [Rule::exists('programas', 'id')],
            'apoderados' => ['array'],
            'apoderados.*' => [Rule::exists('users', 'id')],
        ];
    }

    public function updating($campo): void
    {
        if (in_array($campo, ['buscar', 'filtroGrupo', 'filtroNivel', 'filtroEstado'], true)) {
            $this->resetPage();
        }
    }

    public function nuevo(): void
    {
        $this->reset('editandoId', 'nombre', 'rut', 'fecha_nacimiento', 'grupo_etario',
            'nivel', 'grado_id', 'sede_id', 'telefono_contacto', 'email_contacto', 'programas', 'apoderados');
        $this->activo = true;
        $this->resetErrorBag();
        $this->mostrarModal = true;
    }

    public function editar(Estudiante $estudiante): void
    {
        $this->editandoId = $estudiante->id;
        $this->nombre = $estudiante->nombre;
        $this->rut = $estudiante->rut;
        $this->fecha_nacimiento = $estudiante->fecha_nacimiento?->format('Y-m-d');
        $this->grupo_etario = $estudiante->grupo_etario->value;
        $this->nivel = $estudiante->nivel->value;
        $this->grado_id = (string) ($estudiante->grado_id ?? '');
        $this->sede_id = (string) ($estudiante->sede_id ?? '');
        $this->telefono_contacto = $estudiante->telefono_contacto;
        $this->email_contacto = $estudiante->email_contacto;
        $this->activo = $estudiante->activo;
        $this->programas = $estudiante->programas()->pluck('programas.id')->all();
        $this->apoderados = $estudiante->apoderados()->pluck('users.id')->all();
        $this->resetErrorBag();
        $this->mostrarModal = true;
    }

    public function guardar(): void
    {
        // Los <select> opcionales devuelven '' cuando no se elige nada.
        $this->grado_id = $this->grado_id ?: null;
        $this->sede_id = $this->sede_id ?: null;

        $datos = $this->validate();

        $atributos = collect($datos)->except('programas', 'apoderados')->all();

        if ($this->editandoId) {
            $estudiante = Estudiante::findOrFail($this->editandoId);
            $estudiante->update($atributos);
            Flux::toast(variant: 'success', text: 'Estudiante actualizado.');
        } else {
            $estudiante = Estudiante::create($atributos);
            Flux::toast(variant: 'success', text: 'Estudiante creado.');
        }

        $estudiante->programas()->sync($this->programas);
        $estudiante->apoderados()->sync($this->apoderados);

        $this->mostrarModal = false;
    }

    public function alternarActivo(Estudiante $estudiante): void
    {
        $estudiante->update(['activo' => ! $estudiante->activo]);
    }

    /**
     * Grados disponibles según el grupo etario elegido en el formulario.
     */
    public function gradosDisponibles()
    {
        if ($this->grupo_etario === '') {
            return collect();
        }

        $escala = EscalaGrado::paraGrupo(GrupoEtario::from($this->grupo_etario));

        return Grado::porEscala($escala)->ordenados()->get();
    }

    public function render()
    {
        $query = Estudiante::query()
            ->with(['sede', 'grado'])
            ->when($this->buscar !== '', fn ($q) => $q->where(fn ($sub) => $sub
                ->where('nombre', 'ilike', "%{$this->buscar}%")
                ->orWhere('rut', 'ilike', "%{$this->buscar}%")))
            ->when($this->filtroGrupo !== '', fn ($q) => $q->where('grupo_etario', $this->filtroGrupo))
            ->when($this->filtroNivel !== '', fn ($q) => $q->where('nivel', $this->filtroNivel))
            ->when($this->filtroEstado === 'activos', fn ($q) => $q->where('activo', true))
            ->when($this->filtroEstado === 'inactivos', fn ($q) => $q->where('activo', false));

        return view('livewire.estudiantes.gestion-estudiantes', [
            'estudiantes' => $this->aplicarOrden($query, ['nombre', 'grupo_etario', 'nivel', 'activo'], 'nombre')->paginate(15),
            'grupos' => GrupoEtario::cases(),
            'niveles' => NivelEntrenamiento::cases(),
            'sedes' => Sede::orderBy('nombre')->get(),
            'listaProgramas' => Programa::activos()->ordenados()->get(),
            'listaApoderados' => User::role('apoderado')->orderBy('name')->get(),
        ]);
    }
}
