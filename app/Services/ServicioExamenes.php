<?php

namespace App\Services;

use App\Enums\EstadoAsistencia;
use App\Enums\EstadoConvocatoria;
use App\Enums\EstadoNominacion;
use App\Enums\ResultadoExamen;
use App\Models\Clase;
use App\Models\Convocatoria;
use App\Models\Grado;
use App\Models\Graduacion;
use App\Models\Inscripcion;
use App\Models\Matricula;
use App\Models\Nominacion;
use App\Models\Persona;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Lógica de dominio de los exámenes de grado: elegibilidad sugerida, aplicación
 * de resultados (graduación + historial) y crédito de graduación al instructor
 * acreditado (vía ServicioCreditos).
 */
class ServicioExamenes
{
    public function __construct(private ServicioCreditos $creditos) {}

    /**
     * Instructor a quien se acredita por defecto la graduación de la matrícula:
     * el instructor de su clase (misma sede y grupo etario).
     */
    public function instructorPorDefecto(Matricula $matricula): ?User
    {
        $clase = Clase::activas()
            ->where('sede_id', $matricula->sede_id)
            ->where('grupo_etario', $matricula->grupo_etario->value)
            ->whereNotNull('instructor_id')
            ->first();

        return $clase?->instructor;
    }

    /**
     * Fecha desde la que la matrícula está en su grado actual (última graduación,
     * o su fecha de ingreso si nunca se ha graduado).
     */
    public function fechaDesdeGradoActual(Matricula $matricula): CarbonInterface
    {
        $ultima = $matricula->graduaciones()->latest('fecha')->first();

        return $ultima?->fecha ?? $matricula->fecha_ingreso ?? $matricula->created_at;
    }

    /**
     * Meses que la matrícula lleva en su grado actual.
     */
    public function mesesEnGradoActual(Matricula $matricula): int
    {
        return (int) $this->fechaDesdeGradoActual($matricula)->diffInMonths(now());
    }

    /**
     * Porcentaje de asistencia de la matrícula desde que está en su grado actual.
     * Devuelve null si no hay asistencias registradas.
     */
    public function porcentajeAsistencia(Matricula $matricula): ?int
    {
        $desde = $this->fechaDesdeGradoActual($matricula);

        $total = $matricula->asistencias()->whereDate('fecha', '>=', $desde)->count();

        if ($total === 0) {
            return null;
        }

        $presentes = $matricula->asistencias()
            ->whereDate('fecha', '>=', $desde)
            ->where('estado', EstadoAsistencia::Presente->value)
            ->count();

        return (int) round($presentes / $total * 100);
    }

    /**
     * Indica si la matrícula cumple los criterios automáticos de elegibilidad.
     * Un umbral null en config no filtra (queda a criterio del instructor).
     */
    public function cumpleElegibilidad(Matricula $matricula): bool
    {
        $minAsistencia = config('bekho.examenes.asistencia_minima_pct');
        $minMeses = config('bekho.examenes.meses_minimos_en_grado');

        $asistenciaOk = $minAsistencia === null
            || (($pct = $this->porcentajeAsistencia($matricula)) !== null && $pct >= $minAsistencia);

        $mesesOk = $minMeses === null
            || $this->mesesEnGradoActual($matricula) >= $minMeses;

        return $asistenciaOk && $mesesOk;
    }

    /**
     * Matrículas activas sugeridas para una convocatoria (de su sede), con las
     * métricas de elegibilidad. El instructor confirma con el visto bueno.
     *
     * @return Collection<int, array{matricula: Matricula, meses: int, asistencia: int|null, cumple: bool}>
     */
    public function sugerirElegibles(Convocatoria $convocatoria): Collection
    {
        return Matricula::activas()
            ->when($convocatoria->sede_id, fn ($q) => $q->where('sede_id', $convocatoria->sede_id))
            ->with('persona')
            ->get()
            ->sortBy(fn (Matricula $m) => $m->persona?->nombreCompleto())
            ->values()
            ->map(fn (Matricula $m) => [
                'matricula' => $m,
                'meses' => $this->mesesEnGradoActual($m),
                'asistencia' => $this->porcentajeAsistencia($m),
                'cumple' => $this->cumpleElegibilidad($m),
            ]);
    }

    /**
     * Registra el resultado y la nota de una inscripción (sin graduar todavía).
     */
    public function registrarResultado(Inscripcion $inscripcion, ResultadoExamen $resultado, ?float $nota = null): Inscripcion
    {
        $inscripcion->update([
            'resultado' => $resultado,
            'nota' => $nota,
        ]);

        return $inscripcion;
    }

    /**
     * Aplica la graduación de una inscripción aprobada con visto bueno: crea el
     * historial y otorga el crédito al instructor acreditado (origen + cadena).
     * NO sube el grado de la persona: eso ocurre al registrar la entrega del
     * cinturón. Idempotente por (convocatoria, matrícula).
     */
    public function aplicarGraduacion(Inscripcion $inscripcion): ?Graduacion
    {
        if (! $inscripcion->visto_bueno || ! $inscripcion->resultado?->esAprobado()) {
            return null;
        }

        $yaExiste = Graduacion::where('convocatoria_id', $inscripcion->convocatoria_id)
            ->where('matricula_id', $inscripcion->matricula_id)
            ->exists();

        if ($yaExiste) {
            return null;
        }

        // Los grados que requieren nominación (rojo-negro, danes) no se gradúan sin
        // una nominación aprobada de la persona a ese grado objetivo.
        $gradoDestino = $inscripcion->grado_destino_id ? Grado::find($inscripcion->grado_destino_id) : null;
        if ($gradoDestino?->requiere_nominacion
            && ! $this->tieneNominacionAprobada($inscripcion->matricula->persona, $gradoDestino)) {
            return null;
        }

        return DB::transaction(function () use ($inscripcion): Graduacion {
            $graduacion = Graduacion::create([
                'grupo_id' => $inscripcion->grupo_id,
                'matricula_id' => $inscripcion->matricula_id,
                'convocatoria_id' => $inscripcion->convocatoria_id,
                'grado_origen_id' => $inscripcion->grado_origen_id,
                'grado_destino_id' => $inscripcion->grado_destino_id,
                // Examinador: la persona de la cuenta que examinó (inscripcion).
                'examinador_persona_id' => $inscripcion->instructor_id ? User::find($inscripcion->instructor_id)?->persona_id : null,
                'fecha' => $inscripcion->convocatoria->fecha,
                'resultado' => $inscripcion->resultado,
                'nota' => $inscripcion->nota,
            ]);

            // Instructor acreditado (origen) + créditos de la cadena de supervisión.
            $this->creditos->otorgar($graduacion);

            return $graduacion;
        });
    }

    /**
     * Registra la entrega del cinturón (ceremonia): fija fecha_entrega y recién
     * entonces actualiza el grado actual de la persona (nunca antes). El plazo de
     * 30 días desde la aprobación solo alerta; no caduca la aprobación.
     */
    public function registrarEntrega(Graduacion $graduacion, ?CarbonInterface $fecha = null): Graduacion
    {
        $graduacion->update(['fecha_entrega' => ($fecha ?? now())->toDateString()]);

        if ($graduacion->grado_destino_id) {
            $matricula = Matricula::withoutGlobalScopes()->find($graduacion->matricula_id);
            $persona = $matricula ? Persona::find($matricula->persona_id) : null;
            $persona?->update(['grado_id' => $graduacion->grado_destino_id]);
        }

        return $graduacion;
    }

    /**
     * Graduaciones aprobadas cuyo cinturón aún no se entrega y ya vencieron el
     * plazo de 30 días (solo para alertar; la aprobación no caduca).
     *
     * @return Collection<int, Graduacion>
     */
    public function entregasVencidas(?CarbonInterface $a = null): Collection
    {
        return Graduacion::whereNull('fecha_entrega')
            ->get()
            ->filter(fn (Graduacion $g) => $g->plazoEntregaVencido($a))
            ->values();
    }

    /**
     * Nomina a una persona a un grado objetivo (queda pendiente de aprobación).
     */
    public function nominar(Persona $persona, Grado $gradoObjetivo, ?Persona $nominadoPor = null): Nominacion
    {
        return Nominacion::create([
            'persona_id' => $persona->id,
            'grado_objetivo_id' => $gradoObjetivo->id,
            'nominado_por_persona_id' => $nominadoPor?->id,
            'fecha' => now()->toDateString(),
            'estado' => EstadoNominacion::Pendiente->value,
        ]);
    }

    /**
     * Resuelve una nominación pendiente: aprobada o rechazada.
     */
    public function resolverNominacion(Nominacion $nominacion, bool $aprobar): Nominacion
    {
        $nominacion->update([
            'estado' => ($aprobar ? EstadoNominacion::Aprobada : EstadoNominacion::Rechazada)->value,
        ]);

        return $nominacion;
    }

    /**
     * ¿La persona tiene una nominación APROBADA para ese grado objetivo?
     */
    public function tieneNominacionAprobada(Persona $persona, Grado $gradoObjetivo): bool
    {
        return Nominacion::where('persona_id', $persona->id)
            ->where('grado_objetivo_id', $gradoObjetivo->id)
            ->where('estado', EstadoNominacion::Aprobada->value)
            ->exists();
    }

    /**
     * Finaliza la convocatoria: aplica todas las graduaciones aprobadas y marca
     * el estado como Finalizada.
     */
    public function finalizar(Convocatoria $convocatoria): void
    {
        $convocatoria->inscripciones()->each(fn (Inscripcion $i) => $this->aplicarGraduacion($i));

        $convocatoria->update(['estado' => EstadoConvocatoria::Finalizada]);
    }
}
