<?php

namespace App\Livewire\Examenes;

use App\Enums\EstadoConvocatoria;
use App\Enums\ResultadoExamen;
use App\Livewire\Concerns\SoloLectura;
use App\Models\Convocatoria;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Inscripcion;
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
     * Grado siguiente en la escala del estudiante (destino sugerido).
     */
    protected function siguienteGrado(Estudiante $estudiante): ?Grado
    {
        if (! $estudiante->grado) {
            return Grado::porEscala($estudiante->escalaGrado())->ordenados()->first();
        }

        return Grado::porEscala($estudiante->escalaGrado())
            ->where('orden', '>', $estudiante->grado->orden)
            ->ordenados()
            ->first();
    }

    /**
     * Inscribe a un estudiante en la convocatoria.
     */
    public function inscribir(int $estudianteId, ServicioExamenes $servicio): void
    {
        // El instructor inscribe; el alumno queda inscrito sin aprobación de nadie.
        $this->authorize('inscribir examenes');
        $this->bloqueaSiSoloLectura();

        if ($this->convocatoria->estado === EstadoConvocatoria::Finalizada) {
            return;
        }

        $estudiante = Estudiante::findOrFail($estudianteId);

        Inscripcion::firstOrCreate(
            [
                'convocatoria_id' => $this->convocatoria->id,
                'estudiante_id' => $estudiante->id,
            ],
            [
                'academia_id' => $this->convocatoria->academia_id,
                'grado_origen_id' => $estudiante->grado_id,
                'grado_destino_id' => $this->siguienteGrado($estudiante)?->id,
                'instructor_id' => $servicio->instructorPorDefecto($estudiante)?->id,
            ],
        );

        Flux::toast(variant: 'success', text: 'Estudiante inscrito.');
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
            'ins_nota' => ['nullable', 'numeric', 'between:9.0,9.9'],
        ]);

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
            ->with(['estudiante', 'gradoOrigen', 'gradoDestino', 'instructor'])
            ->get();

        $idsInscritos = $inscritos->pluck('estudiante_id')->all();

        $sugeridos = $servicio->sugerirElegibles($this->convocatoria)
            ->reject(fn ($s) => in_array($s['estudiante']->id, $idsInscritos, true))
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
