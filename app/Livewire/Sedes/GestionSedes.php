<?php

namespace App\Livewire\Sedes;

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
    public ?int $editandoId = null;

    public string $nombre = '';

    public ?string $direccion = null;

    public ?string $comuna = null;

    public ?int $academia_id = null;

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
        return $this->esSuperAdmin() ? $this->academia_id : Auth::user()->academia_id;
    }

    public function nueva(): void
    {
        $this->reset('editandoId', 'nombre', 'direccion', 'comuna', 'academia_id', 'instructores');
        $this->activo = true;

        if (! $this->esSuperAdmin()) {
            $this->academia_id = Auth::user()->academia_id;
        }

        $this->resetErrorBag();
        $this->mostrarModal = true;
    }

    public function editar(Sede $sede): void
    {
        $this->editandoId = $sede->id;
        $this->nombre = $sede->nombre;
        $this->direccion = $sede->direccion;
        $this->comuna = $sede->comuna;
        $this->academia_id = $sede->academia_id;
        $this->activo = $sede->activo;
        $this->instructores = $sede->instructores()->pluck('users.id')->all();
        $this->resetErrorBag();
        $this->mostrarModal = true;
    }

    public function guardar(): void
    {
        $datos = $this->validate();

        $atributos = [
            'nombre' => $datos['nombre'],
            'direccion' => $datos['direccion'],
            'comuna' => $datos['comuna'],
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
        $sede->update(['activo' => ! $sede->activo]);
    }

    public function render()
    {
        return view('livewire.sedes.gestion-sedes', [
            'sedes' => Sede::with('academia')->orderBy('nombre')->get(),
            'academias' => Academia::orderBy('nombre')->get(),
            'listaInstructores' => User::role(['instructor', 'maestro'])->orderBy('name')->get(),
            'comunas' => config('comunas', []),
        ]);
    }
}
