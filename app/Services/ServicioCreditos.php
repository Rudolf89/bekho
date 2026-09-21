<?php

namespace App\Services;

use App\Models\CargoRango;
use App\Models\CreditoGraduacion;
use App\Models\DistintivoRango;
use App\Models\Graduacion;
use App\Models\Instructor;
use App\Models\Matricula;
use App\Models\Persona;
use App\Models\Sede;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Créditos de graduación: sostienen el avance del distintivo de collar del
 * profesor. Cada graduación suma un punto al instructor de origen y a toda su
 * cadena de supervisión hacia arriba (instructores.supervisor_persona_id), hasta
 * el titular de la federación. Cuenta cada graduación por igual (color y Dan).
 *
 * Origen del crédito, en orden:
 *   1. Si la persona graduada tiene matrícula activa: su instructor asignado.
 *   2. Si esa matrícula no tiene instructor: el responsable de la sede.
 *   3. Si no tiene matrícula: su profesor (supervisor de su faceta de instructor).
 *   4. Nunca a sí misma: si el origen es la propia persona, la cadena parte de su
 *      profesor.
 *
 * El distintivo actual NO se guarda: se calcula contando créditos contra los
 * umbrales del rango. Con umbrales nulos, devuelve null sin fallar.
 */
class ServicioCreditos
{
    /**
     * Resuelve la persona de origen del crédito de una persona graduada según las
     * cuatro reglas. Devuelve null si no hay origen posible (p. ej. el titular).
     */
    public function resolverOrigen(Persona $graduado): ?Persona
    {
        $matricula = Matricula::withoutGlobalScopes()->activas()
            ->where('persona_id', $graduado->id)
            ->first();

        if ($matricula) {
            $origen = $this->instructorDeMatricula($matricula)
                ?? $this->responsableDeSede($matricula->sede_id);
        } else {
            $origen = $this->supervisorDe($graduado);
        }

        // Regla 4: nunca a sí misma; si el origen es la propia persona, parte de
        // su profesor.
        if ($origen && $origen->id === $graduado->id) {
            $origen = $this->supervisorDe($graduado);
        }

        return ($origen && $origen->id !== $graduado->id) ? $origen : null;
    }

    /**
     * Cadena de supervisión desde el origen hacia arriba (lo incluye). Protegida
     * contra ciclos: nunca visita dos veces a la misma persona.
     *
     * @return Collection<int, Persona>
     */
    public function cadena(Persona $origen): Collection
    {
        $cadena = collect();
        $visitados = [];
        $actual = $origen;

        while ($actual && ! in_array($actual->id, $visitados, true)) {
            $cadena->push($actual);
            $visitados[] = $actual->id;
            $actual = $this->supervisorDe($actual);
        }

        return $cadena;
    }

    /**
     * Otorga los créditos de una graduación: fija el instructor acreditado (origen)
     * y crea una fila por cada persona de la cadena. Idempotente por (graduación,
     * persona). Pensado para correr dentro de la transacción de la graduación.
     */
    public function otorgar(Graduacion $graduacion): void
    {
        DB::transaction(function () use ($graduacion): void {
            $matricula = Matricula::withoutGlobalScopes()->find($graduacion->matricula_id);
            $graduado = $matricula ? Persona::withTrashed()->find($matricula->persona_id) : null;

            if (! $graduado) {
                return;
            }

            $origen = $this->resolverOrigen($graduado);
            $graduacion->update(['instructor_acreditado_persona_id' => $origen?->id]);

            if (! $origen) {
                return;
            }

            foreach ($this->cadena($origen) as $i => $persona) {
                CreditoGraduacion::firstOrCreate(
                    ['graduacion_id' => $graduacion->id, 'persona_id' => $persona->id],
                    ['posicion' => $i + 1],
                );
            }
        });
    }

    /**
     * Total de créditos de por vida de una persona (todas las graduaciones de su
     * línea, incluidas las propias como origen).
     */
    public function totalCreditos(Persona $persona): int
    {
        return CreditoGraduacion::where('persona_id', $persona->id)->count();
    }

    /**
     * Distintivo actual: el escalón de mayor umbral (no nulo) que el total de
     * créditos alcanza dentro del rango de la persona. Null si no tiene rango o si
     * los umbrales del rango aún no están configurados.
     */
    public function distintivoDe(Persona $persona): ?DistintivoRango
    {
        $rangoId = Instructor::where('persona_id', $persona->id)->value('rango_id');

        if (! $rangoId) {
            return null;
        }

        $total = $this->totalCreditos($persona);

        return DistintivoRango::where('rango_id', $rangoId)
            ->whereNotNull('graduados_requeridos')
            ->where('graduados_requeridos', '<=', $total)
            ->orderByDesc('graduados_requeridos')
            ->first();
    }

    /**
     * Cadena de supervisión por encima de la persona (sus profesores hacia arriba).
     *
     * @return Collection<int, Persona>
     */
    public function cadenaSupervision(Persona $persona): Collection
    {
        $supervisor = $this->supervisorDe($persona);

        return $supervisor ? $this->cadena($supervisor) : collect();
    }

    /**
     * Perfil de créditos de un instructor: rango, distintivo actual, total de
     * créditos y su cadena de supervisión.
     *
     * @return array{rango: ?CargoRango, distintivo: ?DistintivoRango, total: int, cadena: Collection<int, Persona>}
     */
    public function perfilDe(Persona $persona): array
    {
        $instructor = Instructor::with('rango')->where('persona_id', $persona->id)->first();

        return [
            'rango' => $instructor?->rango,
            'distintivo' => $this->distintivoDe($persona),
            'total' => $this->totalCreditos($persona),
            'cadena' => $this->cadenaSupervision($persona),
        ];
    }

    /**
     * Persona del instructor asignado a la matrícula (regla de origen 1).
     */
    private function instructorDeMatricula(Matricula $matricula): ?Persona
    {
        return $matricula->instructor_persona_id
            ? Persona::withTrashed()->find($matricula->instructor_persona_id)
            : null;
    }

    /**
     * Persona responsable de la sede (regla de origen 2).
     */
    private function responsableDeSede(?int $sedeId): ?Persona
    {
        if (! $sedeId) {
            return null;
        }

        $responsableId = Sede::withoutGlobalScopes()->whereKey($sedeId)->value('responsable_persona_id');

        return $responsableId ? Persona::withTrashed()->find((int) $responsableId) : null;
    }

    /**
     * Persona del supervisor (profesor) de una persona, por su faceta de instructor.
     */
    public function supervisorDe(Persona $persona): ?Persona
    {
        $supervisorId = Instructor::where('persona_id', $persona->id)->value('supervisor_persona_id');

        return $supervisorId ? Persona::withTrashed()->find((int) $supervisorId) : null;
    }
}
