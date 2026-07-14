<?php

namespace App\Livewire\Clases;

use App\Enums\DiaSemana;
use App\Enums\GrupoEtario;
use App\Enums\PapelEnClase;
use App\Livewire\Concerns\ConOrden;
use App\Livewire\Concerns\SoloLectura;
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
    use ConOrden, SoloLectura;

    public ?int $editandoId = null;

    public string $nombre = '';

    public string $sede_id = '';

    public ?string $planilla_id = '';

    public string $grupo_etario = '';

    public string $dia_semana = '';

    public ?string $hora_inicio = null;

    public ?string $hora_fin = null;

    public bool $activo = true;

    /**
     * Asignaciones de instructores: una fila por instructor, con su papel.
     * Una clase puede tener varios instructores (titular, asistente, ayudante).
     *
     * @var array<int, array{user_id: string, papel: string}>
     */
    public array $asignaciones = [];

    public bool $mostrarModal = false;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'sede_id' => ['required', Rule::exists('sedes', 'id')],
            'planilla_id' => ['nullable', Rule::exists('planillas', 'id')],
            'grupo_etario' => ['required', Rule::enum(GrupoEtario::class)],
            'dia_semana' => ['required', Rule::enum(DiaSemana::class)],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['nullable', 'date_format:H:i', 'after:hora_inicio'],
            'activo' => ['boolean'],
            'asignaciones' => ['array'],
            'asignaciones.*.user_id' => ['required', Rule::exists('users', 'id')],
            'asignaciones.*.papel' => ['required', Rule::enum(PapelEnClase::class)],
        ];
    }

    public function nuevo(): void
    {
        $this->reset('editandoId', 'nombre', 'sede_id', 'planilla_id', 'grupo_etario',
            'dia_semana', 'hora_inicio', 'hora_fin', 'asignaciones');
        $this->activo = true;
        $this->resetErrorBag();
        $this->mostrarModal = true;
    }

    public function editar(Clase $clase): void
    {
        $this->editandoId = $clase->id;
        $this->nombre = $clase->nombre;
        $this->sede_id = (string) $clase->sede_id;
        $this->planilla_id = (string) ($clase->planilla_id ?? '');
        $this->grupo_etario = $clase->grupo_etario->value;
        $this->dia_semana = (string) $clase->dia_semana->value;
        $this->hora_inicio = substr((string) $clase->hora_inicio, 0, 5);
        $this->hora_fin = $clase->hora_fin ? substr((string) $clase->hora_fin, 0, 5) : null;
        $this->activo = $clase->activo;
        $this->asignaciones = $clase->instructores
            ->map(fn (User $u) => ['user_id' => (string) $u->id, 'papel' => $u->pivot->papel])
            ->all();
        $this->resetErrorBag();
        $this->mostrarModal = true;
    }

    /**
     * Agrega una fila de instructor vacía (papel titular por defecto).
     */
    public function agregarInstructor(): void
    {
        $this->asignaciones[] = ['user_id' => '', 'papel' => PapelEnClase::Titular->value];
    }

    public function quitarInstructor(int $indice): void
    {
        unset($this->asignaciones[$indice]);
        $this->asignaciones = array_values($this->asignaciones);
    }

    public function guardar(): void
    {
        $this->bloqueaSiSoloLectura();

        // Los <select> opcionales devuelven '' cuando no se elige nada; se
        // normaliza a null para que la regla nullable omita 'exists' y para
        // no insertar '' en columnas de llave foránea.
        $this->planilla_id = $this->planilla_id ?: null;

        // Se descartan las filas de instructor sin usuario elegido.
        $this->asignaciones = array_values(array_filter(
            $this->asignaciones,
            fn (array $fila) => ($fila['user_id'] ?? '') !== '',
        ));

        $datos = $this->validate();

        // La clase pertenece a la academia de su sede. Así queda bien también
        // cuando la crea el admin-plataforma, que no tiene academia activa (y por
        // tanto el relleno automático del tenant no aplica).
        $datos['academia_id'] = Sede::sinAcademia()->findOrFail($datos['sede_id'])->academia_id;

        $asignaciones = $datos['asignaciones'] ?? [];
        unset($datos['asignaciones']);

        if ($this->editandoId) {
            $clase = Clase::findOrFail($this->editandoId);
            $clase->update($datos);
            Flux::toast(variant: 'success', text: 'Clase actualizada.');
        } else {
            $clase = Clase::create($datos);
            Flux::toast(variant: 'success', text: 'Clase creada.');
        }

        // [user_id => papel]; si se repite un instructor, prevalece el último papel.
        $papeles = [];
        foreach ($asignaciones as $fila) {
            $papeles[$fila['user_id']] = $fila['papel'];
        }
        $clase->sincronizarInstructores($papeles);

        $this->mostrarModal = false;
    }

    public function alternarActivo(Clase $clase): void
    {
        $this->bloqueaSiSoloLectura();

        $clase->update(['activo' => ! $clase->activo]);
    }

    public function render()
    {
        return view('livewire.clases.gestion-clases', [
            'clases' => $this->aplicarOrden(Clase::with(['sede', 'instructores']), ['dia_semana', 'nombre', 'grupo_etario'], 'dia_semana')
                ->orderBy('hora_inicio')->get(),
            'sedes' => Sede::orderBy('nombre')->get(),
            'instructores' => User::role(['instructor', 'direccion'])->orderBy('name')->get(),
            'planillas' => Planilla::where('activo', true)->orderBy('nombre')->get(),
            'grupos' => GrupoEtario::cases(),
            'dias' => DiaSemana::cases(),
            'papeles' => PapelEnClase::cases(),
        ]);
    }
}
