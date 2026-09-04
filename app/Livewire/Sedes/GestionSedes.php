<?php

namespace App\Livewire\Sedes;

use App\Enums\TipoSede;
use App\Livewire\Concerns\ConTabla;
use App\Livewire\Concerns\SoloLectura;
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
    use ConTabla, SoloLectura;

    public ?int $editandoId = null;

    public string $nombre = '';

    public ?string $direccion = null;

    public ?string $comuna = '';

    public string $tipo = 'academia';

    public bool $privada = false;

    public ?string $academia_id = '';

    public bool $activo = true;

    /** @var array<int, int> */
    public array $instructores = [];

    public bool $mostrarModal = false;

    public function esSuperAdmin(): bool
    {
        return Auth::user()->hasRole('admin-plataforma');
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
            'tipo' => ['required', Rule::enum(TipoSede::class)],
            'privada' => ['boolean'],
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
        $this->reset('editandoId', 'nombre', 'direccion', 'comuna', 'tipo', 'privada', 'academia_id', 'instructores');
        $this->activo = true;

        if (! $this->esSuperAdmin()) {
            $this->academia_id = (string) Auth::user()->academia_id;
        }

        $this->resetErrorBag();
        $this->mostrarModal = true;
    }

    public function editar(Sede $sede): void
    {
        $this->editandoId = $sede->id;
        $this->nombre = $sede->nombre;
        $this->direccion = $sede->direccion;
        $this->comuna = (string) ($sede->comuna ?? '');
        $this->tipo = $sede->tipo->value;
        $this->privada = $sede->privada;
        $this->academia_id = (string) ($sede->academia_id ?? '');
        $this->activo = $sede->activo;
        $this->instructores = $sede->instructores()->pluck('users.id')->all();
        $this->resetErrorBag();
        $this->mostrarModal = true;
    }

    public function guardar(): void
    {
        $this->bloqueaSiSoloLectura();

        $this->comuna = $this->comuna ?: null;

        $datos = $this->validate();

        $atributos = [
            'nombre' => $datos['nombre'],
            'direccion' => $datos['direccion'],
            'comuna' => $datos['comuna'],
            'tipo' => $datos['tipo'],
            'privada' => $datos['privada'],
            'academia_id' => $this->academiaEfectiva(),
            'activo' => $this->activo,
        ];

        if ($this->editandoId) {
            $sede = Sede::findOrFail($this->editandoId);
            $sede->update($atributos);
            Flux::toast(variant: 'success', text: 'Sede actualizada.');
        } else {
            $sede = Sede::create($atributos);
            Flux::toast(variant: 'success', text: 'Sede creada.');
        }

        $sede->instructores()->sync($this->instructores);

        $this->mostrarModal = false;
    }

    public function alternarActivo(Sede $sede): void
    {
        $this->bloqueaSiSoloLectura();

        $sede->update(['activo' => ! $sede->activo]);
    }

    public function render()
    {
        // El aislamiento por academia lo maneja el tenant: el admin-plataforma (que
        // no filtra lecturas) ve todas las sedes e instructores; el maestro,
        // solo los de su academia.
        return view('livewire.sedes.gestion-sedes', [
            'sedes' => $this->aplicarOrden(
                $this->aplicarBusqueda(Sede::with('academia'), ['nombre', 'comuna', 'direccion', 'academia.nombre']),
                ['nombre', 'comuna', 'activo'], 'nombre'
            )->get(),
            'academias' => Academia::orderBy('nombre')->get(),
            'listaInstructores' => User::role(['instructor', 'direccion'])->orderBy('name')->get(),
            'comunas' => config('comunas', []),
            'tipos' => TipoSede::cases(),
        ]);
    }
}
