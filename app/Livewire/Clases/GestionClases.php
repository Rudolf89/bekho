<?php

namespace App\Livewire\Clases;

use App\Enums\DiaSemana;
use App\Enums\GrupoEtario;
use App\Enums\PapelEnClase;
use App\Livewire\Concerns\ConTabla;
use App\Livewire\Concerns\SoloLectura;
use App\Models\Clase;
use App\Models\Planilla;
use App\Models\Sede;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Clases y horario')]
class GestionClases extends Component
{
    use ConTabla, SoloLectura;

    public ?int $editandoId = null;

    public string $nombre = '';

    public string $sede_id = '';

    public ?string $planilla_id = '';

    public string $grupo_etario = '';

    public ?string $cupo_maximo = null;

    public bool $activo = true;

    /**
     * Horarios de la clase: una fila por día + hora. Una clase puede reunirse
     * varios días (p. ej. lunes y miércoles).
     *
     * @var array<int, array{dia_semana: string, hora_inicio: string, hora_fin: string}>
     */
    public array $horarios = [];

    /**
     * El nombre se autogenera a partir del grupo etario y la sede mientras el
     * usuario no lo escriba a mano. Si lo edita (o al editar una clase existente)
     * se respeta lo que haya puesto.
     */
    public bool $nombreAuto = true;

    /** Duración por defecto de una clase, en minutos. */
    public const DURACION_MINUTOS = 45;

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
            'cupo_maximo' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'activo' => ['boolean'],
            'horarios' => ['required', 'array', 'min:1'],
            'horarios.*.dia_semana' => ['required', Rule::enum(DiaSemana::class)],
            'horarios.*.hora_inicio' => ['required', 'date_format:H:i'],
            'horarios.*.hora_fin' => ['required', 'date_format:H:i', 'after:horarios.*.hora_inicio'],
            'asignaciones' => ['array'],
            'asignaciones.*.user_id' => ['required', Rule::exists('users', 'id')],
            'asignaciones.*.papel' => ['required', Rule::enum(PapelEnClase::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'horarios.required' => 'Agrega al menos un horario.',
            'horarios.min' => 'Agrega al menos un horario.',
            'horarios.*.dia_semana.required' => 'Elige el día.',
            'horarios.*.hora_inicio.required' => 'Indica la hora de inicio.',
            'horarios.*.hora_fin.required' => 'Indica la hora de fin.',
            'horarios.*.hora_fin.after' => 'La hora de fin debe ser posterior al inicio.',
        ];
    }

    public function nuevo(): void
    {
        $this->reset('editandoId', 'nombre', 'sede_id', 'planilla_id', 'grupo_etario',
            'cupo_maximo', 'horarios', 'asignaciones', 'nombreAuto');
        $this->activo = true;
        $this->agregarHorario();
        $this->resetErrorBag();
        $this->mostrarModal = true;
    }

    // --- Autocompletado del nombre y de la hora de fin -----------------------

    public function updatedNombre(string $value): void
    {
        // Al escribir se deja de autogenerar; al vaciarlo, se retoma el automático.
        $this->nombreAuto = trim($value) === '';
        $this->regenerarNombre();
    }

    public function updatedSedeId(): void
    {
        $this->regenerarNombre();
    }

    public function updatedGrupoEtario(): void
    {
        $this->regenerarNombre();
    }

    /**
     * Al cambiar la hora de inicio de un horario, autocompleta su hora de fin a
     * inicio + 45 min si aún está vacía (queda editable).
     */
    public function updatedHorarios(mixed $value, ?string $key = null): void
    {
        if ($key === null || ! str_ends_with($key, '.hora_inicio')) {
            return;
        }

        $indice = (int) explode('.', $key)[0];

        if (($this->horarios[$indice]['hora_fin'] ?? '') !== '') {
            return;
        }

        try {
            $this->horarios[$indice]['hora_fin'] = Carbon::createFromFormat('H:i', (string) $value)
                ->addMinutes(self::DURACION_MINUTOS)
                ->format('H:i');
        } catch (\Exception) {
            // Hora de inicio incompleta o inválida: no se autocompleta todavía.
        }
    }

    /**
     * Regenera el nombre sugerido a partir de los campos elegidos, si el usuario
     * no lo ha escrito a mano.
     */
    protected function regenerarNombre(): void
    {
        if ($this->nombreAuto) {
            $this->nombre = $this->nombreSugerido();
        }
    }

    /**
     * Nombre sugerido: "Grupo · Sede" con las partes ya elegidas.
     */
    protected function nombreSugerido(): string
    {
        $partes = [];

        if ($this->grupo_etario !== '') {
            $partes[] = GrupoEtario::tryFrom($this->grupo_etario)?->etiqueta();
        }

        if ($this->sede_id !== '') {
            $partes[] = Sede::sinGrupo()->find($this->sede_id)?->nombre;
        }

        return implode(' · ', array_filter($partes));
    }

    // --- Horarios ------------------------------------------------------------

    public function agregarHorario(): void
    {
        $this->horarios[] = ['dia_semana' => '', 'hora_inicio' => '', 'hora_fin' => ''];
    }

    public function quitarHorario(int $indice): void
    {
        unset($this->horarios[$indice]);
        $this->horarios = array_values($this->horarios);
    }

    public function editar(Clase $clase): void
    {
        // Se respeta lo guardado: no se autogenera el nombre al editar.
        $this->nombreAuto = false;

        $this->editandoId = $clase->id;
        $this->nombre = $clase->nombre;
        $this->sede_id = (string) $clase->sede_id;
        $this->planilla_id = (string) ($clase->planilla_id ?? '');
        $this->grupo_etario = $clase->grupo_etario->value;
        $this->cupo_maximo = $clase->cupo_maximo !== null ? (string) $clase->cupo_maximo : null;
        $this->activo = $clase->activo;
        $this->horarios = $clase->horarios
            ->map(fn ($h) => [
                'dia_semana' => (string) $h->dia_semana->value,
                'hora_inicio' => substr((string) $h->hora_inicio, 0, 5),
                'hora_fin' => substr((string) $h->hora_fin, 0, 5),
            ])
            ->all();

        if ($this->horarios === []) {
            $this->agregarHorario();
        }

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
        $this->cupo_maximo = $this->cupo_maximo !== null && $this->cupo_maximo !== '' ? $this->cupo_maximo : null;

        // Se descartan las filas de instructor sin usuario elegido.
        $this->asignaciones = array_values(array_filter(
            $this->asignaciones,
            fn (array $fila) => ($fila['user_id'] ?? '') !== '',
        ));

        $datos = $this->validate();

        // La clase pertenece al grupo de su sede. Así queda bien también cuando
        // la crea el admin-plataforma, que no tiene grupo activo (y por tanto el
        // relleno automático del tenant no aplica).
        $datos['grupo_id'] = Sede::sinGrupo()->findOrFail($datos['sede_id'])->grupo_id;

        $asignaciones = $datos['asignaciones'] ?? [];
        $horarios = $datos['horarios'];
        unset($datos['asignaciones'], $datos['horarios']);

        if ($this->editandoId) {
            $clase = Clase::findOrFail($this->editandoId);
            $clase->update($datos);
            Flux::toast(variant: 'success', text: 'Clase actualizada.');
        } else {
            $clase = Clase::create($datos);
            Flux::toast(variant: 'success', text: 'Clase creada.');
        }

        $this->sincronizarHorarios($clase, $horarios);

        // [user_id => papel]; si se repite un instructor, prevalece el último papel.
        $papeles = [];
        foreach ($asignaciones as $fila) {
            $papeles[$fila['user_id']] = $fila['papel'];
        }
        $clase->sincronizarInstructores($papeles);

        $this->mostrarModal = false;
    }

    /**
     * Reemplaza los horarios de la clase por los del formulario (deduplicando
     * por día + hora de inicio, que es la clave única en la base).
     *
     * @param  array<int, array{dia_semana: string, hora_inicio: string, hora_fin: string}>  $horarios
     */
    protected function sincronizarHorarios(Clase $clase, array $horarios): void
    {
        $clase->horarios()->delete();

        $vistos = [];
        foreach ($horarios as $h) {
            $clave = $h['dia_semana'].'|'.$h['hora_inicio'];
            if (isset($vistos[$clave])) {
                continue;
            }
            $vistos[$clave] = true;

            $clase->horarios()->create([
                'dia_semana' => (int) $h['dia_semana'],
                'hora_inicio' => $h['hora_inicio'],
                'hora_fin' => $h['hora_fin'],
            ]);
        }
    }

    public function alternarActivo(Clase $clase): void
    {
        $this->bloqueaSiSoloLectura();

        $clase->update(['activo' => ! $clase->activo]);
    }

    public function render()
    {
        return view('livewire.clases.gestion-clases', [
            'clases' => $this->aplicarOrden(
                $this->aplicarBusqueda(Clase::with(['sede', 'instructores', 'horarios']), ['nombre', 'sede.nombre']),
                ['nombre', 'grupo_etario'], 'nombre'
            )->get(),
            'sedes' => Sede::orderBy('nombre')->get(),
            'instructores' => User::role(['instructor', 'direccion'])->orderBy('name')->get(),
            'planillas' => Planilla::where('activo', true)->orderBy('nombre')->get(),
            'grupos' => GrupoEtario::cases(),
            'dias' => DiaSemana::cases(),
            'papeles' => PapelEnClase::cases(),
        ]);
    }
}
