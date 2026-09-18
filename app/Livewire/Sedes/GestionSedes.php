<?php

namespace App\Livewire\Sedes;

use App\Enums\TipoSede;
use App\Livewire\Concerns\ConTabla;
use App\Livewire\Concerns\SoloLectura;
use App\Models\Grupo;
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

    public ?string $region = '';

    public ?string $capacidad = null;

    public string $tipo = 'grupo';

    public bool $privada = false;

    public ?string $grupo_id = '';

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
            'region' => ['nullable', 'string', Rule::in(array_keys(config('regiones', [])))],
            'comuna' => ['nullable', 'string', 'max:255'],
            'capacidad' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'tipo' => ['required', Rule::enum(TipoSede::class)],
            'privada' => ['boolean'],
            'grupo_id' => [$this->esSuperAdmin() ? 'required' : 'nullable', Rule::exists('grupos', 'id')],
            'activo' => ['boolean'],
            'instructores' => ['array'],
            'instructores.*' => [Rule::exists('users', 'id')],
        ];
    }

    protected function grupoEfectiva(): ?int
    {
        $id = $this->esSuperAdmin() ? $this->grupo_id : Auth::user()->grupo_id;

        return $id !== '' && $id !== null ? (int) $id : null;
    }

    /**
     * Al cambiar de región se limpia la comuna elegida (los selectores son
     * dependientes: la región filtra las comunas).
     */
    public function updatedRegion(): void
    {
        $this->comuna = '';
    }

    public function nueva(): void
    {
        $this->reset('editandoId', 'nombre', 'direccion', 'comuna', 'region', 'capacidad', 'tipo', 'privada', 'grupo_id', 'instructores');
        $this->activo = true;

        if (! $this->esSuperAdmin()) {
            $this->grupo_id = (string) Auth::user()->grupo_id;
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
        $this->region = (string) ($sede->region ?? '');
        $this->capacidad = $sede->capacidad !== null ? (string) $sede->capacidad : null;
        $this->tipo = $sede->tipo->value;
        $this->privada = $sede->privada;
        $this->grupo_id = (string) ($sede->grupo_id ?? '');
        $this->activo = $sede->activo;
        $this->instructores = $sede->instructores()->pluck('users.id')->all();
        $this->resetErrorBag();
        $this->mostrarModal = true;
    }

    public function guardar(): void
    {
        $this->bloqueaSiSoloLectura();

        $this->comuna = $this->comuna ?: null;
        $this->region = $this->region ?: null;
        $this->capacidad = $this->capacidad !== null && $this->capacidad !== '' ? $this->capacidad : null;

        $datos = $this->validate();

        $atributos = [
            'nombre' => $datos['nombre'],
            'direccion' => $datos['direccion'],
            'comuna' => $datos['comuna'],
            'region' => $datos['region'],
            'capacidad' => $datos['capacidad'],
            'tipo' => $datos['tipo'],
            'privada' => $datos['privada'],
            'grupo_id' => $this->grupoEfectiva(),
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

    /**
     * Regiones disponibles (claves del catálogo).
     *
     * @return array<int, string>
     */
    public function regiones(): array
    {
        return array_keys(config('regiones', []));
    }

    /**
     * Comunas de la región elegida (selector dependiente).
     *
     * @return array<int, string>
     */
    public function comunas(): array
    {
        return config('regiones.'.$this->region, []);
    }

    public function render()
    {
        // El aislamiento por grupo lo maneja el tenant: el admin-plataforma (que
        // no filtra lecturas) ve todas las sedes e instructores; el maestro,
        // solo los de su grupo.
        return view('livewire.sedes.gestion-sedes', [
            'sedes' => $this->aplicarOrden(
                $this->aplicarBusqueda(Sede::with('grupo'), ['nombre', 'comuna', 'direccion', 'grupo.nombre']),
                ['nombre', 'comuna', 'activo'], 'nombre'
            )->get(),
            'grupos' => Grupo::orderBy('nombre')->get(),
            'listaInstructores' => User::role(['instructor', 'direccion'])->orderBy('name')->get(),
            'regiones' => $this->regiones(),
            'comunasRegion' => $this->comunas(),
            'tipos' => TipoSede::cases(),
        ]);
    }
}
