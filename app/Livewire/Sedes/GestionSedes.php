<?php

namespace App\Livewire\Sedes;

use App\Livewire\Concerns\ConOrden;
use App\Models\Academia;
use App\Models\Sede;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Sedes')]
class GestionSedes extends Component
{
    use ConOrden;

    public ?int $editandoId = null;

    public string $nombre = '';

    public ?string $direccion = null;

    public ?string $comuna = '';

    public ?string $academia_id = '';

    public bool $activo = true;

    /** @var array<int, int> */
    public array $instructores = [];

    public bool $mostrarModal = false;

    public function esSuperAdmin(): bool
    {
        return Auth::user()->hasRole('super-admin');
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'comuna' => ['nullable', 'string', 'max:255'],
            'academia_id' => [$this->esSuperAdmin() ? 'required' : 'nullable', Rule::exists('academias', 'id')],
            'activo' => ['boolean'],
            'instructores' => ['array'],
            'instructores.*' => [Rule::exists('users', 'id')],
        ];
    }

    protected function academiaEfectiva(): ?int
    {
        $id = $this->esSuperAdmin() ? $this->academia_id : Auth::user()->academia_id;

        return $id !== '' && $id !== null ? (int) $id : null;
    }

    public function nueva(): void
    {
        $this->reset('editandoId', 'nombre', 'direccion', 'comuna', 'academia_id', 'instructores');
        $this->activo = true;

        if (! $this->esSuperAdmin()) {
            $this->academia_id = (string) Auth::user()->academia_id;
        }

        $this->resetErrorBag();
        $this->mostrarModal = true;
    }

    /**
     * Busca una sede. El super-admin puede gestionar sedes de cualquier
     * academia, así que ignora el aislamiento por academia.
     */
    protected function buscarSede(int $id): Sede
    {
        $query = Sede::query();

        if ($this->esSuperAdmin()) {
            $query->withoutGlobalScope('academia');
        }

        return $query->findOrFail($id);
    }

    public function editar(int $sedeId): void
    {
        $sede = $this->buscarSede($sedeId);

        $this->editandoId = $sede->id;
        $this->nombre = $sede->nombre;
        $this->direccion = $sede->direccion;
        $this->comuna = (string) ($sede->comuna ?? '');
        $this->academia_id = (string) ($sede->academia_id ?? '');
        $this->activo = $sede->activo;
        $this->instructores = $sede->instructores()->pluck('users.id')->all();
        $this->resetErrorBag();
        $this->mostrarModal = true;
    }

    public function guardar(): void
    {
        $this->comuna = $this->comuna ?: null;

        $datos = $this->validate();

        $atributos = [
            'nombre' => $datos['nombre'],
            'direccion' => $datos['direccion'],
            'comuna' => $datos['comuna'],
            'academia_id' => $this->academiaEfectiva(),
            'activo' => $this->activo,
        ];

        if ($this->editandoId) {
            $sede = $this->buscarSede($this->editandoId);
            $sede->update($atributos);
            Flux::toast(variant: 'success', text: 'Sede actualizada.');
        } else {
            $sede = Sede::create($atributos);
            Flux::toast(variant: 'success', text: 'Sede creada.');
        }

        $sede->instructores()->sync($this->instructores);

        $this->mostrarModal = false;
    }

    public function alternarActivo(int $sedeId): void
    {
        $sede = $this->buscarSede($sedeId);
        $sede->update(['activo' => ! $sede->activo]);
    }

    public function render()
    {
        // El super-admin ve TODAS las sedes (y todos los instructores para
        // asignarlas), no solo las de su academia activa.
        $consultaSedes = Sede::with('academia');
        $consultaInstructores = User::role(['instructor', 'maestro']);

        if ($this->esSuperAdmin()) {
            $consultaSedes->withoutGlobalScope('academia');
            $consultaInstructores->withoutGlobalScope('academia');
        }

        return view('livewire.sedes.gestion-sedes', [
            'sedes' => $this->aplicarOrden($consultaSedes, ['nombre', 'comuna', 'activo'], 'nombre')->get(),
            'academias' => Academia::orderBy('nombre')->get(),
            'listaInstructores' => $consultaInstructores->orderBy('name')->get(),
            'comunas' => config('comunas', []),
        ]);
    }
}
