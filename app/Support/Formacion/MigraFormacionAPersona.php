<?php

namespace App\Support\Formacion;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Traslada la formación del modelo viejo (colgada de user_id) al nuevo (colgada
 * de persona_id): inscripciones_legacy → inscripciones_programa, horas_legacy →
 * horas_programa (manuales, congeladas), cumplimiento_requisitos →
 * cumplimientos_requisito, y rellena progreso_contenidos.persona_id.
 *
 * Idempotente y sin pérdida: usa el DB query builder (sin scopes ni modelos) y
 * no borra nada del modelo viejo. Un user sin persona NO se puede resolver: su
 * inscripción queda FUERA de la migración y se reporta (no se inventa persona).
 * Los cumplimientos cuyo requisito viejo no tiene equivalente en el nuevo
 * catálogo (los requisitos se resembraron desde legacy_niveles.json) se omiten
 * best-effort; la inscripción y las horas siempre se conservan.
 *
 * @phpstan-type Resumen array{inscripciones: int, horas: int, cumplimientos: int, progreso: int, huerfanos: list<int>, requisitos_sin_mapa: int}
 */
class MigraFormacionAPersona
{
    /**
     * @return Resumen
     */
    public function ejecutar(): array
    {
        $resumen = [
            'inscripciones' => 0,
            'horas' => 0,
            'cumplimientos' => 0,
            'progreso' => 0,
            'huerfanos' => [],
            'requisitos_sin_mapa' => 0,
        ];

        // El modelo viejo puede ya no existir (tablas retiradas en el commit d):
        // en ese caso no hay nada que trasladar.
        if (Schema::hasTable('inscripciones_legacy')) {
            $this->migrarInscripciones($resumen);
        }

        $this->rellenarProgreso($resumen);

        return $resumen;
    }

    /**
     * @param  Resumen  $resumen
     */
    private function migrarInscripciones(array &$resumen): void
    {
        $legacy = DB::table('programas')->where('nombre', 'Legacy')->first();
        if (! $legacy) {
            return;
        }

        // Etapas del programa Legacy indexadas por orden (para mapear el nivel viejo).
        $etapasPorOrden = DB::table('etapas_programa')
            ->where('programa_id', $legacy->id)
            ->whereNotNull('horas_requeridas')
            ->pluck('id', 'orden');

        foreach (DB::table('inscripciones_legacy')->orderBy('id')->get() as $insc) {
            $personaId = DB::table('users')->where('id', $insc->user_id)->value('persona_id');

            if (! $personaId) {
                $resumen['huerfanos'][] = (int) $insc->id;

                continue;
            }

            $nivel = DB::table('niveles_legacy')->where('id', $insc->nivel_legacy_id)->first();
            $etapaId = $nivel ? ($etapasPorOrden[$nivel->orden] ?? null) : null;

            $inscProgramaId = $this->upsertInscripcion($legacy->id, (int) $personaId, $etapaId, $insc);
            $resumen['inscripciones']++;

            $this->migrarHoras((int) $insc->id, $inscProgramaId, $resumen);
            $this->migrarCumplimientos((int) $insc->id, $inscProgramaId, $etapaId, $resumen);
        }
    }

    /**
     * Crea/actualiza la inscripción al programa por (persona, programa) y devuelve su id.
     */
    private function upsertInscripcion(int $programaId, int $personaId, ?int $etapaId, object $insc): int
    {
        $aprobadoPorPersona = $insc->aprobado_por
            ? DB::table('users')->where('id', $insc->aprobado_por)->value('persona_id')
            : null;

        $clave = ['persona_id' => $personaId, 'programa_id' => $programaId];
        $valores = [
            'etapa_actual_id' => $etapaId,
            'estado' => $insc->estado,
            'fecha_ingreso' => $insc->fecha_inicio,
            'fecha_aprobacion' => $insc->fecha_aprobacion,
            'aprobado_por_persona_id' => $aprobadoPorPersona,
            'nota' => $insc->nota,
            'updated_at' => now(),
        ];

        $existente = DB::table('inscripciones_programa')->where($clave)->first();
        if ($existente) {
            DB::table('inscripciones_programa')->where('id', $existente->id)->update($valores);

            return (int) $existente->id;
        }

        return (int) DB::table('inscripciones_programa')->insertGetId($clave + $valores + ['created_at' => now()]);
    }

    /**
     * @param  Resumen  $resumen
     */
    private function migrarHoras(int $inscLegacyId, int $inscProgramaId, array &$resumen): void
    {
        foreach (DB::table('horas_legacy')->where('inscripcion_legacy_id', $inscLegacyId)->get() as $hora) {
            $clave = [
                'inscripcion_programa_id' => $inscProgramaId,
                'fecha' => $hora->fecha,
                'horas' => $hora->horas,
                'descripcion' => $hora->descripcion,
            ];

            if (DB::table('horas_programa')->where($clave)->exists()) {
                continue;
            }

            DB::table('horas_programa')->insert($clave + [
                'origen' => 'manual',
                'registrado_por_user_id' => $hora->verificado_por,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $resumen['horas']++;
        }
    }

    /**
     * @param  Resumen  $resumen
     */
    private function migrarCumplimientos(int $inscLegacyId, int $inscProgramaId, ?int $etapaId, array &$resumen): void
    {
        if (! $etapaId) {
            return;
        }

        foreach (DB::table('cumplimiento_requisitos')->where('inscripcion_legacy_id', $inscLegacyId)->get() as $cumpl) {
            $textoViejo = DB::table('requisitos_legacy')->where('id', $cumpl->requisito_legacy_id)->value('texto');
            $requisitoEtapaId = $this->mapearRequisito($etapaId, (string) $textoViejo);

            if (! $requisitoEtapaId) {
                $resumen['requisitos_sin_mapa']++;

                continue;
            }

            $clave = ['inscripcion_programa_id' => $inscProgramaId, 'requisito_etapa_id' => $requisitoEtapaId];
            if (DB::table('cumplimientos_requisito')->where($clave)->exists()) {
                continue;
            }

            $aprobadoPorPersona = $cumpl->verificado_por
                ? DB::table('users')->where('id', $cumpl->verificado_por)->value('persona_id')
                : null;

            DB::table('cumplimientos_requisito')->insert($clave + [
                'cumplido_at' => $cumpl->verificado_at,
                'aprobado_por_persona_id' => $aprobadoPorPersona,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $resumen['cumplimientos']++;
        }
    }

    /**
     * Mapea un requisito viejo (texto) a uno nuevo de la etapa. Exacto primero;
     * si no, la prueba escrita se reconoce por palabra clave. Null si no hay mapa.
     */
    private function mapearRequisito(int $etapaId, string $textoViejo): ?int
    {
        $exacto = DB::table('requisitos_etapa')
            ->where('etapa_programa_id', $etapaId)
            ->where('descripcion', $textoViejo)
            ->value('id');

        if ($exacto) {
            return (int) $exacto;
        }

        if (stripos($textoViejo, 'escrito') !== false) {
            $escrito = DB::table('requisitos_etapa')
                ->where('etapa_programa_id', $etapaId)
                ->where('tipo', 'cuestionario')
                ->value('id');

            return $escrito ? (int) $escrito : null;
        }

        return null;
    }

    /**
     * Rellena progreso_contenidos.persona_id (desde el user) y registrado_por_user_id.
     *
     * @param  Resumen  $resumen
     */
    private function rellenarProgreso(array &$resumen): void
    {
        foreach (DB::table('progreso_contenidos')->whereNull('persona_id')->orderBy('id')->get() as $progreso) {
            $personaId = DB::table('users')->where('id', $progreso->user_id)->value('persona_id');

            if (! $personaId) {
                continue;
            }

            DB::table('progreso_contenidos')->where('id', $progreso->id)->update([
                'persona_id' => $personaId,
                'registrado_por_user_id' => $progreso->registrado_por_user_id ?? $progreso->user_id,
                'updated_at' => now(),
            ]);
            $resumen['progreso']++;
        }
    }
}
