<?php

namespace App\Services;

use App\Enums\EstadoLegacy;
use App\Models\AsistenciaAyudante;
use App\Models\Clase;
use App\Models\HoraPrograma;
use App\Models\HorarioClase;
use App\Models\InscripcionPrograma;
use App\Models\Persona;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Auth;

/**
 * Horas del Programa Legacy calculadas desde la asistencia con papel de AYUDANTE.
 *
 * Al marcar a un trainee como ayudante de una clase en una fecha, se congela la
 * cantidad de horas (= suma de la duración de los horarios de esa clase ese día)
 * y se acredita a su inscripción activa cuya etapa en curso exige horas
 * (típicamente Legacy). Las horas NO se recalculan si luego cambia el horario.
 */
class ServicioHorasAyudante
{
    /**
     * Horas dictadas por la clase en la fecha dada: suma de (hora_fin − hora_inicio)
     * de los horarios cuyo día de la semana coincide con la fecha.
     */
    public function horasDeClaseEnFecha(Clase $clase, CarbonInterface $fecha): float
    {
        $dia = $fecha->dayOfWeekIso; // 1 (lunes) … 7 (domingo)

        return (float) $clase->horarios
            ->filter(fn (HorarioClase $h) => $h->dia_semana->value === $dia)
            ->sum(fn (HorarioClase $h) => Carbon::parse($h->hora_fin)
                ->diffInHours(Carbon::parse($h->hora_inicio), absolute: true));
    }

    /**
     * Registra (idempotente por persona×clase×fecha) la asistencia de ayudante y,
     * si corresponde, acredita las horas congeladas a la inscripción activa que
     * las exige. Devuelve la marca.
     */
    public function marcar(Persona $persona, Clase $clase, CarbonInterface $fecha, ?int $registradoPor = null): AsistenciaAyudante
    {
        $horas = $this->horasDeClaseEnFecha($clase, $fecha);
        // Normaliza al inicio del día para que el cast date del where coincida con
        // el valor almacenado (evita insertar dos veces la misma sesión).
        $fechaDia = $fecha->copy()->startOfDay();

        $marca = AsistenciaAyudante::firstOrCreate(
            ['persona_id' => $persona->id, 'clase_id' => $clase->id, 'fecha' => $fechaDia],
            ['grupo_id' => $clase->grupo_id, 'horas' => $horas, 'registrado_por_user_id' => $registradoPor ?? Auth::id()],
        );

        // Solo acredita al crear la marca (no duplica al re-marcar).
        if ($marca->wasRecentlyCreated && $horas > 0) {
            if ($inscripcion = $this->inscripcionQueExigeHoras($persona)) {
                $inscripcion->horas()->create([
                    'fecha' => $fecha->toDateString(),
                    'horas' => $horas,
                    'origen' => 'asistencia',
                    'asistencia_ayudante_id' => $marca->id,
                    'descripcion' => 'Asistencia como ayudante · '.$clase->nombre,
                    'registrado_por_user_id' => $registradoPor ?? Auth::id(),
                ]);
            }
        }

        return $marca;
    }

    /**
     * Deshace la marca y la hora que originó (las demás horas quedan intactas).
     */
    public function quitar(AsistenciaAyudante $marca): void
    {
        HoraPrograma::where('asistencia_ayudante_id', $marca->id)->delete();
        $marca->delete();
    }

    /**
     * Inscripción En curso de la persona cuya etapa actual exige horas (Legacy u
     * otro programa con horas_requeridas), o null.
     */
    private function inscripcionQueExigeHoras(Persona $persona): ?InscripcionPrograma
    {
        return InscripcionPrograma::where('persona_id', $persona->id)
            ->where('estado', EstadoLegacy::EnCurso->value)
            ->whereHas('etapaActual', fn ($q) => $q->whereNotNull('horas_requeridas'))
            ->first();
    }
}
