<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rediseño (regla "sin cascadas: restrictOnDelete + SoftDeletes"): las FK del
 * HISTORIAL dejan de borrarse en cascada. Antes, borrar en duro una persona o
 * una matrícula arrastraba asistencias, pagos, cargos, graduaciones, etc. — se
 * perdía el registro histórico sin dejar rastro. Con restrictOnDelete el borrado
 * duro FALLA si hay historial: el camino correcto es la baja lógica
 * (SoftDeletes) o la desactivación, nunca el forceDelete.
 *
 * Solo se corrigen las FK peligrosas (historial que no debe desaparecer). Las
 * cascadas de COMPOSICIÓN legítima (el hijo no existe sin su padre: pago_cargo,
 * personal_grupo_rol, hijos de planilla de competencia, bloques/cuadrantes de
 * planificación, catálogos colgados de la federación) se dejan intactas.
 *
 * matriculas.persona_id y matriculas.grupo_id ya eran restrictOnDelete desde su
 * migración de origen, así que no se tocan aquí.
 *
 * Reversible: down() vuelve a dejar las FK en cascadeOnDelete.
 */
return new class extends Migration
{
    /**
     * FK a redefinir: tabla => [ [columna, tabla_referenciada], ... ].
     *
     * @var array<string, array<int, array{0: string, 1: string}>>
     */
    private array $fks = [
        // Todo lo que cuelga de una matrícula (historial operativo). Los pagos ya
        // NO cuelgan de la matrícula (se recablearon a cargos vía pago_cargo en
        // 000020): su historial queda protegido por cargos.matricula_id.
        'asistencias' => [['matricula_id', 'matriculas']],
        'cargos' => [['matricula_id', 'matriculas']],
        'becas' => [['matricula_id', 'matriculas']],
        'suspensiones' => [['matricula_id', 'matriculas']],
        'notas_matricula' => [['matricula_id', 'matriculas']],
        'graduaciones' => [['matricula_id', 'matriculas']],
        'inscripciones' => [['matricula_id', 'matriculas']],
        // Identidad y facetas transversales colgadas de la persona.
        'solicitudes_traslado' => [['persona_id', 'personas']],
        'tutelas' => [
            ['apoderado_persona_id', 'personas'],
            ['alumno_persona_id', 'personas'],
        ],
        'instructores' => [['persona_id', 'personas']],
        'personal_grupo' => [
            ['persona_id', 'personas'],
            ['grupo_id', 'grupos'],
        ],
        'documentos_persona' => [['persona_id', 'personas']],
    ];

    public function up(): void
    {
        $this->redefinir(restrictiva: true);
    }

    public function down(): void
    {
        $this->redefinir(restrictiva: false);
    }

    /**
     * Rehace cada FK: la elimina y la vuelve a crear con la acción de borrado
     * pedida (restrictOnDelete al subir, cascadeOnDelete al revertir). El drop y
     * la recreación van en llamadas Schema::table separadas: SQLite reconstruye
     * la tabla al tocar una FK y no admite quitarla y volver a agregarla en la
     * misma pasada.
     */
    private function redefinir(bool $restrictiva): void
    {
        foreach ($this->fks as $tabla => $columnas) {
            Schema::table($tabla, function (Blueprint $table) use ($columnas) {
                foreach ($columnas as [$columna, $referenciada]) {
                    $table->dropForeign([$columna]);
                }
            });

            Schema::table($tabla, function (Blueprint $table) use ($columnas, $restrictiva) {
                foreach ($columnas as [$columna, $referenciada]) {
                    $fk = $table->foreign($columna)->references('id')->on($referenciada);
                    $restrictiva ? $fk->restrictOnDelete() : $fk->cascadeOnDelete();
                }
            });
        }
    }
};
