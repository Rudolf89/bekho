<?php

namespace App\Livewire\Estudiantes;

use App\Enums\EscalaGrado;
use App\Enums\EstadoMatricula;
use App\Enums\GrupoEtario;
use App\Enums\NivelEntrenamiento;
use App\Livewire\Concerns\ConOrden;
use App\Livewire\Concerns\SugiereGrupoEtario;
use App\Models\Grado;
use App\Models\Matricula;
use App\Models\Persona;
use App\Models\Sede;
use App\Support\Rut;
use Flux\Flux;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Gestión de alumnos (matrículas). La identidad vive en la persona; la matrícula
 * es el vínculo con el grupo. "Alta rápida" crea persona + matrícula; la
 * inscripción completa vive en InscribirAlumno.
 */
#[Title('Alumnos')]
class GestionEstudiantes extends Component
{
    use AuthorizesRequests, ConOrden, SugiereGrupoEtario, WithPagination;

    // Filtros
    public string $buscar = '';

    public string $filtroGrupo = '';

    public string $filtroNivel = '';

    public string $filtroEstado = 'activos';

    // Formulario (matrícula + persona)
    public ?int $editandoId = null;

    public string $nombre = '';

    public ?string $rut = null;

    public ?string $fecha_nacimiento = null;

    public string $grupo_etario = '';

    public ?string $grado_id = '';

    public ?string $sede_id = '';

    public ?string $telefono_contacto = null;

    public ?string $email_contacto = null;

    public bool $activo = true;

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
            'grado_id' => ['nullable', Rule::exists('grados', 'id')],
            'sede_id' => ['nullable', Rule::exists('sedes', 'id')],
            'telefono_contacto' => ['nullable', 'string', 'max:50'],
            'email_contacto' => ['nullable', 'email', 'max:255'],
            'activo' => ['boolean'],
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
        $this->authorize('create', Matricula::class);

        $this->reset('editandoId', 'nombre', 'rut', 'fecha_nacimiento', 'grupo_etario',
            'grado_id', 'sede_id', 'telefono_contacto', 'email_contacto');
        $this->activo = true;
        $this->resetErrorBag();
        $this->mostrarModal = true;
    }

    public function editar(Matricula $matricula): void
    {
        $this->authorize('update', $matricula);

        $persona = $matricula->persona;
        $this->editandoId = $matricula->id;
        $this->nombre = $persona->nombreCompleto();
        $this->rut = $persona->documentos()->where('tipo', 'rut')->value('numero');
        $this->fecha_nacimiento = $persona->fecha_nacimiento?->format('Y-m-d');
        $this->grupo_etario = $matricula->grupo_etario->value;
        $this->grado_id = (string) ($persona->grado_id ?? '');
        $this->sede_id = (string) ($matricula->sede_id ?? '');
        $this->telefono_contacto = $persona->telefono;
        $this->email_contacto = $persona->email;
        $this->activo = $matricula->estado === EstadoMatricula::Activa;
        $this->resetErrorBag();
        $this->mostrarModal = true;
    }

    public function guardar(): void
    {
        $this->grado_id = $this->grado_id ?: null;
        $this->sede_id = $this->sede_id ?: null;

        $datos = $this->validate();

        if ($this->editandoId) {
            $matricula = Matricula::findOrFail($this->editandoId);
            $this->authorize('update', $matricula);
            $persona = $matricula->persona;
        } else {
            $this->authorize('create', Matricula::class);
            $persona = new Persona;
            $matricula = new Matricula(['estado' => EstadoMatricula::Activa->value, 'fecha_ingreso' => now()->toDateString()]);
        }

        // Persona (identidad): el nombre completo va a "nombres" en la alta rápida.
        $persona->fill([
            'nombres' => $datos['nombre'],
            'fecha_nacimiento' => $datos['fecha_nacimiento'] ?? $persona->fecha_nacimiento ?? now()->subYears(10)->toDateString(),
            'telefono' => $datos['telefono_contacto'],
            'email' => $datos['email_contacto'],
            'grado_id' => $datos['grado_id'],
        ])->save();

        if ($datos['rut']) {
            $persona->documentos()->updateOrCreate(
                ['tipo' => 'rut'],
                ['numero' => Rut::normalizar($datos['rut']) ?? $datos['rut'], 'pais' => 'CL', 'principal' => true],
            );
        }

        $matricula->fill([
            'persona_id' => $persona->id,
            'grupo_etario' => $datos['grupo_etario'],
            'sede_id' => $datos['sede_id'],
            'nivel' => $this->nivelDerivado()->value,
            'estado' => $this->activo ? EstadoMatricula::Activa->value : EstadoMatricula::Retirada->value,
        ])->save();

        Flux::toast(variant: 'success', text: $this->editandoId ? 'Alumno actualizado.' : 'Alumno creado.');
        $this->mostrarModal = false;
    }

    public function alternarActivo(Matricula $matricula): void
    {
        $this->authorize('update', $matricula);

        $matricula->update([
            'estado' => $matricula->estado === EstadoMatricula::Activa
                ? EstadoMatricula::Retirada->value
                : EstadoMatricula::Activa->value,
        ]);
    }

    /**
     * Grados disponibles según el grupo etario elegido en el formulario.
     */
    public function gradosDisponibles()
    {
        if ($this->grupo_etario === '') {
            return collect();
        }

        return Grado::porEscala(EscalaGrado::paraGrupo(GrupoEtario::from($this->grupo_etario)))->ordenados()->get();
    }

    /**
     * Nivel que tendrá el alumno según el cinturón elegido (sin cinturón => Principiantes).
     */
    public function nivelDerivado(): NivelEntrenamiento
    {
        return $this->grado_id
            ? (Grado::find($this->grado_id)?->nivelEntrenamiento() ?? NivelEntrenamiento::Principiantes)
            : NivelEntrenamiento::Principiantes;
    }

    public function render()
    {
        $query = Matricula::query()
            ->visiblePara(auth()->user())
            ->with(['persona.grado', 'sede'])
            ->when($this->buscar !== '', fn ($q) => $q->where(fn ($sub) => $sub
                ->whereHas('persona', fn ($p) => $p
                    ->where('nombres', 'like', "%{$this->buscar}%")
                    ->orWhere('apellido_paterno', 'like', "%{$this->buscar}%")
                    ->orWhere('apellido_materno', 'like', "%{$this->buscar}%"))
                ->orWhereHas('persona.documentos', fn ($d) => $d->where('numero', 'like', "%{$this->buscar}%"))))
            ->when($this->filtroGrupo !== '', fn ($q) => $q->where('grupo_etario', $this->filtroGrupo))
            ->when($this->filtroNivel !== '', fn ($q) => $q->where('nivel', $this->filtroNivel))
            ->when($this->filtroEstado === 'activos', fn ($q) => $q->where('estado', EstadoMatricula::Activa->value))
            ->when($this->filtroEstado === 'inactivos', fn ($q) => $q->where('estado', '!=', EstadoMatricula::Activa->value));

        return view('livewire.estudiantes.gestion-estudiantes', [
            'matriculas' => $this->aplicarOrden($query, ['grupo_etario', 'nivel', 'estado'], 'grupo_etario')->paginate(15),
            'grupos' => GrupoEtario::cases(),
            'niveles' => NivelEntrenamiento::cases(),
            'sedes' => Sede::orderBy('nombre')->get(),
        ]);
    }
}
