<?php

namespace App\Livewire\Programas;

use App\Enums\EstadoLegacy;
use App\Livewire\Concerns\ConTabla;
use App\Models\AsistenciaAyudante;
use App\Models\Clase;
use App\Models\InscripcionPrograma;
use App\Models\Persona;
use App\Models\Programa;
use App\Models\RequisitoEtapa;
use App\Services\ServicioHorasAyudante;
use App\Support\Tenancy\Grupo as Tenant;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Gestión de inscripciones a programas formativos (antes "Panel Legacy"). El
 * instructor/dirección inscribe personas, registra horas y verifica requisitos;
 * el ascenso lo aprueba el instructor del alumno (matrícula activa) o, en su
 * defecto, dirección/licenciatario ('aprobar ascensos'). La supervisión es solo
 * informativa.
 */
#[Title('Inscripciones a programas')]
class GestionInscripciones extends Component
{
    use ConTabla;

    public string $nuevaPersonaId = '';

    public string $nuevoProgramaId = '';

    #[Url]
    public ?int $inscripcionId = null;

    public string $horaFecha = '';

    public string $horaCantidad = '';

    public string $horaDescripcion = '';

    // Registro de asistencia como ayudante (horas calculadas del horario).
    public string $ayudanteClaseId = '';

    public string $ayudanteFecha = '';

    public function crearInscripcion(): void
    {
        abort_unless(Auth::user()->can('gestionar inscripciones'), 403);

        $this->validate([
            'nuevaPersonaId' => ['required', 'exists:personas,id'],
            'nuevoProgramaId' => ['required', 'exists:programas,id'],
        ], [], ['nuevaPersonaId' => 'persona', 'nuevoProgramaId' => 'programa']);

        $programa = Programa::find($this->nuevoProgramaId);
        $primeraEtapa = $programa->etapas()->orderBy('orden')->first();

        $inscripcion = InscripcionPrograma::firstOrCreate(
            ['persona_id' => $this->nuevaPersonaId, 'programa_id' => $this->nuevoProgramaId],
            ['etapa_actual_id' => $primeraEtapa?->id, 'estado' => EstadoLegacy::EnCurso->value, 'fecha_ingreso' => now()->toDateString()],
        );

        $this->reset('nuevaPersonaId', 'nuevoProgramaId');
        $this->inscripcionId = $inscripcion->id;
    }

    public function agregarHora(): void
    {
        abort_unless(Auth::user()->can('gestionar inscripciones'), 403);

        $inscripcion = $this->inscripcion();
        if (! $inscripcion) {
            return;
        }

        $this->validate([
            'horaFecha' => ['required', 'date'],
            'horaCantidad' => ['required', 'numeric', 'min:0.5', 'max:24'],
        ], [], ['horaFecha' => 'fecha', 'horaCantidad' => 'horas']);

        $inscripcion->horas()->create([
            'fecha' => $this->horaFecha,
            'horas' => $this->horaCantidad,
            'origen' => 'manual',
            'descripcion' => $this->horaDescripcion ?: null,
            'registrado_por_user_id' => Auth::id(),
        ]);

        $this->reset('horaFecha', 'horaCantidad', 'horaDescripcion');
    }

    public function eliminarHora(int $horaId): void
    {
        abort_unless(Auth::user()->can('gestionar inscripciones'), 403);

        $this->inscripcion()?->horas()->whereKey($horaId)->delete();
    }

    public function alternarRequisito(int $requisitoId): void
    {
        abort_unless(Auth::user()->can('gestionar inscripciones'), 403);

        $inscripcion = $this->inscripcion();
        $requisito = RequisitoEtapa::find($requisitoId);
        if (! $inscripcion || ! $requisito || $requisito->esAutomatico()) {
            return;
        }

        $cumplimiento = $inscripcion->cumplimientos()->where('requisito_etapa_id', $requisitoId)->first();

        if ($cumplimiento) {
            $cumplimiento->delete();
        } else {
            $inscripcion->cumplimientos()->create([
                'requisito_etapa_id' => $requisitoId,
                'cumplido_at' => now(),
                'aprobado_por_persona_id' => Auth::user()->persona_id,
            ]);
        }
    }

    /**
     * Registra la asistencia del trainee como ayudante: congela las horas del
     * horario de esa clase ese día y las acredita a su inscripción que las exige.
     */
    public function registrarAyudante(ServicioHorasAyudante $servicio): void
    {
        abort_unless(Auth::user()->can('gestionar inscripciones'), 403);

        $inscripcion = $this->inscripcion();
        if (! $inscripcion) {
            return;
        }

        $this->validate([
            'ayudanteClaseId' => ['required', 'exists:clases,id'],
            'ayudanteFecha' => ['required', 'date'],
        ], [], ['ayudanteClaseId' => 'clase', 'ayudanteFecha' => 'fecha']);

        $clase = Clase::with('horarios')->find($this->ayudanteClaseId);
        $marca = $servicio->marcar($inscripcion->persona, $clase, Carbon::parse($this->ayudanteFecha), Auth::user()?->id);

        $this->reset('ayudanteClaseId', 'ayudanteFecha');

        if ((float) $marca->horas <= 0) {
            Flux::toast(variant: 'warning', text: 'Esa clase no tiene horario ese día: no se sumaron horas.');
        }
    }

    public function quitarAyudante(int $marcaId, ServicioHorasAyudante $servicio): void
    {
        abort_unless(Auth::user()->can('gestionar inscripciones'), 403);

        if ($marca = AsistenciaAyudante::find($marcaId)) {
            $servicio->quitar($marca);
        }
    }

    public function aprobar(): void
    {
        $inscripcion = $this->inscripcion();
        if (! $inscripcion) {
            return;
        }

        abort_unless($this->puedeAprobar($inscripcion), 403);

        if (! $inscripcion->puedeAprobar()) {
            Flux::toast(variant: 'warning', text: 'Aún no cumple las horas y todos los requisitos.');

            return;
        }

        $etapa = $inscripcion->etapaActual;

        $inscripcion->ascensos()->create([
            'etapa_programa_id' => $etapa->id,
            'fecha' => now()->toDateString(),
            'aprobado_por_persona_id' => Auth::user()->persona_id,
        ]);

        // Avanza a la siguiente etapa del programa; si no hay, la inscripción queda aprobada.
        $siguiente = $inscripcion->programa->etapas()->where('orden', '>', $etapa->orden)->orderBy('orden')->first();

        $inscripcion->update($siguiente
            ? ['etapa_actual_id' => $siguiente->id]
            : ['estado' => EstadoLegacy::Aprobado->value, 'fecha_aprobacion' => now()->toDateString(), 'aprobado_por_persona_id' => Auth::user()->persona_id]);

        Flux::toast(variant: 'success', text: 'Ascenso aprobado.');
    }

    /**
     * ¿Puede el usuario actual aprobar el ascenso de esta inscripción? El
     * instructor de la matrícula activa del alumno, o dirección/licenciatario.
     */
    public function puedeAprobar(InscripcionPrograma $inscripcion): bool
    {
        $usuario = Auth::user();

        if ($usuario->can('aprobar ascensos')) {
            return true;
        }

        $instructorId = $inscripcion->persona?->matriculaActiva?->instructor_persona_id;

        return $instructorId !== null && $usuario->persona_id === $instructorId;
    }

    private function inscripcion(): ?InscripcionPrograma
    {
        return $this->inscripcionId
            ? InscripcionPrograma::with(['persona.matriculaActiva', 'programa', 'etapaActual.requisitos.cuestionario', 'horas', 'cumplimientos'])->find($this->inscripcionId)
            : null;
    }

    public function render(): View
    {
        $inscripciones = $this->aplicarBusqueda(
            InscripcionPrograma::with(['persona', 'programa', 'etapaActual'])->withSum('horas as horas_total', 'horas'),
            ['persona.nombres', 'programa.nombre'],
        )->latest()->get();

        $personas = Persona::query()
            ->when(Tenant::id(), fn ($q, $id) => $q->whereHas('matriculas', fn ($m) => $m->where('grupo_id', $id)))
            ->orderBy('nombres')->get();

        $inscripcion = $this->inscripcion();
        $marcasAyudante = $inscripcion
            ? AsistenciaAyudante::where('persona_id', $inscripcion->persona_id)->with('clase')->orderByDesc('fecha')->get()
            : collect();

        return view('livewire.programas.gestion-inscripciones', [
            'inscripciones' => $inscripciones,
            'horasTotales' => $inscripciones->sum('horas_total'),
            'inscripcion' => $inscripcion,
            'personas' => $personas,
            'programas' => Programa::activos()->has('etapas')->ordenados()->get(),
            'puedeAprobarActual' => $inscripcion ? $this->puedeAprobar($inscripcion) : false,
            'clases' => Clase::where('activo', true)->orderBy('nombre')->get(),
            'marcasAyudante' => $marcasAyudante,
        ]);
    }
}
