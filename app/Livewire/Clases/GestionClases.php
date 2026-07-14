<?php

namespace App\Livewire\Clases;

use App\Enums\DiaSemana;
use App\Enums\GrupoEtario;
use App\Livewire\Concerns\ConOrden;
use App\Models\Clase;
use App\Models\Planilla;
use App\Models\Sede;
use App\Models\User;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Clases y horario')]
class GestionClases extends Component
{
    use ConOrden;

    public ?int $editandoId = null;

    public string $nombre = '';

    public string $sede_id = '';

    public ?string $instructor_id = '';

    public ?string $planilla_id = '';

    public string $grupo_etario = '';

    public string $dia_semana = '';

    public ?string $hora_inicio = null;

    public ?string $hora_fin = null;

    public bool $activo = true;

    public bool $mostrarModal = false;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'sede_id' => ['required', Rule::exists('sedes', 'id')],
            'instructor_id' => ['nullable', Rule::exists('users', 'id')],
            'planilla_id' => ['nullable', Rule::exists('planillas', 'id')],
            'grupo_etario' => ['required', Rule::enum(GrupoEtario::class)],
            'dia_semana' => ['required', Rule::enum(DiaSemana::class)],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['nullable', 'date_format:H:i', 'after:hora_inicio'],
            'activo' => ['boolean'],
        ];
    }

    public function nuevo(): void
    {
        $this->reset('editandoId', 'nombre', 'sede_id', 'instructor_id', 'planilla_id', 'grupo_etario',
            'dia_semana', 'hora_inicio', 'hora_fin');
        $this->activo = true;
        $this->resetErrorBag();
        $this->mostrarModal = true;
    }

    public function editar(Clase $clase): void
    {
        $this->editandoId = $clase->id;
        $this->nombre = $clase->nombre;
        $this->sede_id = (string) $clase->sede_id;
        $this->instructor_id = (string) ($clase->instructor_id ?? '');
        $this->planilla_id = (string) ($clase->planilla_id ?? '');
        $this->grupo_etario = $clase->grupo_etario->value;
        $this->dia_semana = (string) $clase->dia_semana->value;
        $this->hora_inicio = substr((string) $clase->hora_inicio, 0, 5);
        $this->hora_fin = $clase->hora_fin ? substr((string) $clase->hora_fin, 0, 5) : null;
        $this->activo = $clase->activo;
        $this->resetErrorBag();
        $this->mostrarModal = true;
    }

    public function guardar(): void
    {
        // Los <select> opcionales devuelven '' cuando no se elige nada; se
        // normaliza a null para que la regla nullable omita 'exists' y para
        // no insertar '' en columnas de llave foránea.
        $this->instructor_id = $this->instructor_id ?: null;
        $this->planilla_id = $this->planilla_id ?: null;

        $datos = $this->validate();

        if ($this->editandoId) {
            Clase::findOrFail($this->editandoId)->update($datos);
            Flux::toast(variant: 'success', text: 'Clase actualizada.');
        } else {
            Clase::create($datos);
            Flux::toast(variant: 'success', text: 'Clase creada.');
        }

        $this->mostrarModal = false;
    }

    public function alternarActivo(Clase $clase): void
    {
        $clase->update(['activo' => ! $clase->activo]);
    }

    public function render()
    {
        return view('livewire.clases.gestion-clases', [
            'clases' => $this->aplicarOrden(Clase::with(['sede', 'instructor']), ['dia_semana', 'nombre', 'grupo_etario'], 'dia_semana')
                ->orderBy('hora_inicio')->get(),
            'sedes' => Sede::orderBy('nombre')->get(),
            'instructores' => User::role(['instructor', 'maestro'])->orderBy('name')->get(),
            'planillas' => Planilla::where('activo', true)->orderBy('nombre')->get(),
            'grupos' => GrupoEtario::cases(),
            'dias' => DiaSemana::cases(),
        ]);
    }
}
