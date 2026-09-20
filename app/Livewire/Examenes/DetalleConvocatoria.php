<?php

namespace App\Livewire\Examenes;

use App\Enums\EstadoConvocatoria;
use App\Enums\ResultadoExamen;
use App\Livewire\Concerns\SoloLectura;
use App\Models\Convocatoria;
use App\Models\Grado;
use App\Models\Inscripcion;
use App\Models\Matricula;
use App\Models\User;
use App\Services\ServicioExamenes;
use Flux\Flux;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Convocatoria')]
class DetalleConvocatoria extends Component
{
    use AuthorizesRequests, SoloLectura;

    public Convocatoria $convocatoria;

    // Edición de una inscripción
    public ?int $inscripcionEditandoId = null;

    public ?string $ins_grado_destino = '';

    public ?string $ins_instructor = '';

    public bool $ins_visto_bueno = false;

    public ?string $ins_resultado = '';

    public ?float $ins_nota = null;

    public bool $mostrarModal = false;

    public function mount(Convocatoria $convocatoria): void
    {
        $this->convocatoria = $convocatoria;
    }

    /**
     * Grado siguiente en la escala de la matrícula (destino sugerido).
     */
    protected function siguienteGrado(Matricula $matricula): ?Grado
    {
        $gradoActual = $matricula->persona?->grado;

        if (! $gradoActual) {
            return Grado::porEscala($matricula->escalaGrado())->ordenados()->first();
        }

        return Grado::porEscala($matricula->escalaGrado())
            ->where('orden', '>', $gradoActual->orden)
            ->ordenados()
            ->first();
    }

    /**
     * Inscribe una matrícula (alumno) en la convocatoria.
     */
    public function inscribir(int $matriculaId, ServicioExamenes $servicio): void
    {
        // El instructor inscribe; el alumno queda inscrito sin aprobación de nadie.
        $this->authorize('inscribir examenes');
        $this->bloqueaSiSoloLectura();

        if ($this->convocatoria->estado === EstadoConvocatoria::Finalizada) {
            return;
        }

        $matricula = Matricula::with('persona')->findOrFail($matriculaId);

        Inscripcion::firstOrCreate(
            [
                'convocatoria_id' => $this->convocatoria->id,
                'matricula_id' => $matricula->id,
            ],
            [
                'grupo_id' => $this->convocatoria->grupo_id,
                'grado_origen_id' => $matricula->persona?->grado_id,
                'grado_destino_id' => $this->siguienteGrado($matricula)?->id,
                'instructor_id' => $servicio->instructorPorDefecto($matricula)?->id,
            ],
        );

        Flux::toast(variant: 'success', text: 'Alumno inscrito.');
    }

    /**
     * Reglas de la nota según el tipo de convocatoria: en las de instructor se
     * valida la escala (config); en las demás no se rinde con esta nota.
     *
     * @return list<string>
     */
    protected function reglasNota(): array
    {
        if (! $this->convocatoria->tipo->usaNota()) {
            return ['nullable', 'numeric'];
        }

        $min = config('bekho.examenes.nota.minima');
        $max = config('bekho.examenes.nota.maxima');

        return ['nullable', 'numeric', "between:{$min},{$max}"];
    }

    public function abrirEdicion(Inscripcion $inscripcion): void
    {
        // Ajustar grado destino, nota o visto bueno es gestión, no inscripción.
        $this->authorize('gestionar examenes');

        $this->inscripcionEditandoId = $inscripcion->id;
        $this->ins_grado_destino = (string) ($inscripcion->grado_destino_id ?? '');
        $this->ins_instructor = (string) ($inscripcion->instructor_id ?? '');
        $this->ins_visto_bueno = $inscripcion->visto_bueno;
        $this->ins_resultado = $inscripcion->resultado?->value ?? '';
        $this->ins_nota = $inscripcion->nota !== null ? (float) $inscripcion->nota : null;
        $this->resetErrorBag();
        $this->mostrarModal = true;
    }

    public function guardarEdicion(ServicioExamenes $servicio): void
    {
        $this->authorize('gestionar examenes');
        $this->bloqueaSiSoloLectura();

        // Los <select> opcionales devuelven '' cuando no se elige nada.
        $this->ins_grado_destino = $this->ins_grado_destino ?: null;
        $this->ins_instructor = $this->ins_instructor ?: null;
        $this->ins_resultado = $this->ins_resultado ?: null;

        $datos = $this->validate([
            'ins_grado_destino' => ['nullable', Rule::exists('grados', 'id')],
            'ins_instructor' => ['nullable', Rule::exists('users', 'id')],
            'ins_visto_bueno' => ['boolean'],
            'ins_resultado' => ['nullable', Rule::enum(ResultadoExamen::class)],
            'ins_nota' => $this->reglasNota(),
        ]);

        // La nota solo se exige en convocatorias de instructor: para aprobar debe
        // alcanzar el mínimo de aprobación de la escala.
        if ($this->convocatoria->tipo->usaNota()) {
            $resultado = $datos['ins_resultado'] ? ResultadoExamen::from($datos['ins_resultado']) : null;
            $aprobacion = (float) config('bekho.examenes.nota.aprobacion');

            if ($resultado?->esAprobado()
                && ($datos['ins_nota'] === null || (float) $datos['ins_nota'] < $aprobacion)) {
                $this->addError('ins_nota', "Para aprobar, la nota debe ser al menos {$aprobacion}.");

                return;
            }
        }

        $inscripcion = Inscripcion::findOrFail($this->inscripcionEditandoId);
        $inscripcion->update([
            'grado_destino_id' => $datos['ins_grado_destino'],
            'instructor_id' => $datos['ins_instructor'],
            'visto_bueno' => $datos['ins_visto_bueno'],
            'resultado' => $datos['ins_resultado'],
            'nota' => $datos['ins_nota'],
        ]);

        Flux::toast(variant: 'success', text: 'Inscripción actualizada.');
        $this->mostrarModal = false;
    }

    public function eliminar(Inscripcion $inscripcion): void
    {
        // Quitar una inscripción es parte de inscribir (el instructor puede deshacer).
        $this->authorize('inscribir examenes');
        $this->bloqueaSiSoloLectura();

        if ($this->convocatoria->estado !== EstadoConvocatoria::Finalizada) {
            $inscripcion->delete();
        }
    }

    public function finalizar(ServicioExamenes $servicio): void
    {
        // Finalizar aplica las graduaciones: es gestión.
        $this->authorize('gestionar examenes');
        $this->bloqueaSiSoloLectura();

        $servicio->finalizar($this->convocatoria);
        $this->convocatoria->refresh();

        Flux::toast(variant: 'success', text: 'Convocatoria finalizada: se aplicaron las graduaciones.');
    }

    public function render(ServicioExamenes $servicio)
    {
        $inscritos = $this->convocatoria->inscripciones()
            ->with(['matricula.persona', 'gradoOrigen', 'gradoDestino', 'instructor'])
            ->get();

        $idsInscritos = $inscritos->pluck('matricula_id')->all();

        $sugeridos = $servicio->sugerirElegibles($this->convocatoria)
            ->reject(fn ($s) => in_array($s['matricula']->id, $idsInscritos, true))
            ->values();

        return view('livewire.examenes.detalle-convocatoria', [
            'inscritos' => $inscritos,
            'sugeridos' => $sugeridos,
            'grados' => Grado::ordenados()->get(),
            'instructores' => User::role(['instructor', 'direccion'])->orderBy('name')->get(),
            'resultados' => ResultadoExamen::cases(),
            'finalizada' => $this->convocatoria->estado === EstadoConvocatoria::Finalizada,
        ]);
    }
}
