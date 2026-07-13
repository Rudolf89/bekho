<?php

namespace App\Services;

use App\Enums\EstadoAsistencia;
use App\Enums\EstadoConvocatoria;
use App\Enums\ResultadoExamen;
use App\Models\Clase;
use App\Models\Convocatoria;
use App\Models\Estudiante;
use App\Models\Graduacion;
use App\Models\Inscripcion;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Lógica de dominio de los exámenes de grado: elegibilidad sugerida, aplicación
 * de resultados (graduación + historial) y conteo en cascada por instructor.
 */
class ServicioExamenes
{
    /**
     * Instructor a quien se acredita por defecto la graduación del estudiante:
     * el instructor de su clase (misma sede, grupo etario y nivel).
     */
    public function instructorPorDefecto(Estudiante $estudiante): ?User
    {
        $clase = Clase::activas()
            ->where('sede_id', $estudiante->sede_id)
            ->where('grupo_etario', $estudiante->grupo_etario->value)
            ->where('nivel', $estudiante->nivel->value)
            ->whereNotNull('instructor_id')
            ->first();

        return $clase?->instructor;
    }

    /**
     * Fecha desde la que el estudiante está en su grado actual (última
     * graduación, o su fecha de alta si nunca se ha graduado).
     */
    public function fechaDesdeGradoActual(Estudiante $estudiante): CarbonInterface
    {
        $ultima = $estudiante->graduaciones()->latest('fecha')->first();

        return $ultima?->fecha ?? $estudiante->created_at;
    }

    /**
     * Meses que el estudiante lleva en su grado actual.
     */
    public function mesesEnGradoActual(Estudiante $estudiante): int
    {
        return (int) $this->fechaDesdeGradoActual($estudiante)->diffInMonths(now());
    }

    /**
     * Porcentaje de asistencia del estudiante desde que está en su grado actual.
     * Devuelve null si no hay asistencias registradas.
     */
    public function porcentajeAsistencia(Estudiante $estudiante): ?int
    {
        $desde = $this->fechaDesdeGradoActual($estudiante);

        $total = $estudiante->asistencias()->whereDate('fecha', '>=', $desde)->count();

        if ($total === 0) {
            return null;
        }

        $presentes = $estudiante->asistencias()
            ->whereDate('fecha', '>=', $desde)
            ->where('estado', EstadoAsistencia::Presente->value)
            ->count();

        return (int) round($presentes / $total * 100);
    }

    /**
     * Indica si el estudiante cumple los criterios automáticos de elegibilidad.
     * Un umbral null en config no filtra (queda a criterio del instructor).
     */
    public function cumpleElegibilidad(Estudiante $estudiante): bool
    {
        $minAsistencia = config('bekho.examenes.asistencia_minima_pct');
        $minMeses = config('bekho.examenes.meses_minimos_en_grado');

        $asistenciaOk = $minAsistencia === null
            || (($pct = $this->porcentajeAsistencia($estudiante)) !== null && $pct >= $minAsistencia);

        $mesesOk = $minMeses === null
            || $this->mesesEnGradoActual($estudiante) >= $minMeses;

        return $asistenciaOk && $mesesOk;
    }

    /**
     * Estudiantes activos sugeridos para una convocatoria (de su sede), con las
     * métricas de elegibilidad. El instructor confirma con el visto bueno.
     *
     * @return Collection<int, array{estudiante: Estudiante, meses: int, asistencia: int|null, cumple: bool}>
     */
    public function sugerirElegibles(Convocatoria $convocatoria): Collection
    {
        return Estudiante::activos()
            ->when($convocatoria->sede_id, fn ($q) => $q->where('sede_id', $convocatoria->sede_id))
            ->orderBy('nombre')
            ->get()
            ->map(fn (Estudiante $e) => [
                'estudiante' => $e,
                'meses' => $this->mesesEnGradoActual($e),
                'asistencia' => $this->porcentajeAsistencia($e),
                'cumple' => $this->cumpleElegibilidad($e),
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
     * historial y sube el grado del estudiante. Idempotente por inscripción.
     */
    public function aplicarGraduacion(Inscripcion $inscripcion): ?Graduacion
    {
        if (! $inscripcion->visto_bueno || ! $inscripcion->resultado?->esAprobado()) {
            return null;
        }

        $yaExiste = Graduacion::where('convocatoria_id', $inscripcion->convocatoria_id)
            ->where('estudiante_id', $inscripcion->estudiante_id)
            ->exists();

        if ($yaExiste) {
            return null;
        }

        $graduacion = Graduacion::create([
            'academia_id' => $inscripcion->academia_id,
            'estudiante_id' => $inscripcion->estudiante_id,
            'convocatoria_id' => $inscripcion->convocatoria_id,
            'grado_origen_id' => $inscripcion->grado_origen_id,
            'grado_destino_id' => $inscripcion->grado_destino_id,
            'instructor_id' => $inscripcion->instructor_id,
            'fecha' => $inscripcion->convocatoria->fecha,
            'resultado' => $inscripcion->resultado,
            'nota' => $inscripcion->nota,
        ]);

        if ($inscripcion->grado_destino_id) {
            $inscripcion->estudiante->update(['grado_id' => $inscripcion->grado_destino_id]);
        }

        return $graduacion;
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

    /**
     * IDs del instructor y de toda su línea descendente (por supervisor_id).
     *
     * @return array<int, int>
     */
    public function lineaDescendente(User $instructor): array
    {
        $ids = [$instructor->id];
        $pendientes = [$instructor->id];

        while ($pendientes !== []) {
            $hijos = User::sinAcademia()
                ->whereIn('supervisor_id', $pendientes)
                ->whereNotIn('id', $ids)
                ->pluck('id')
                ->all();

            $ids = array_merge($ids, $hijos);
            $pendientes = $hijos;
        }

        return $ids;
    }

    /**
     * Total de graduaciones acreditadas al instructor incluyendo toda su línea
     * descendente (conteo en cascada).
     */
    public function conteoEnCascada(User $instructor): int
    {
        return Graduacion::whereIn('instructor_id', $this->lineaDescendente($instructor))->count();
    }

    /**
     * Collar de máster alcanzado según el conteo en cascada y los umbrales de
     * config('bekho.premios_collar'). Devuelve null si aún no hay umbrales
     * configurados o no se alcanza ninguno.
     */
    public function collarDe(User $instructor): ?string
    {
        $total = $this->conteoEnCascada($instructor);
        $umbrales = config('bekho.premios_collar', []);

        $alcanzado = null;
        foreach (['azul', 'plateado', 'dorado'] as $collar) {
            $umbral = $umbrales[$collar] ?? null;
            if ($umbral !== null && $total >= $umbral) {
                $alcanzado = $collar;
            }
        }

        return $alcanzado;
    }
}
