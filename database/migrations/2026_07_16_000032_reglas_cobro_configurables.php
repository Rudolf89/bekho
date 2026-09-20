<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reglas del reglamento como DATOS (el sistema puede servir a otra federación).
 *
 * Nivel: sede con respaldo de la federación (el cobro ya es por sede). La
 * federación lleva el valor base (no nulo, con los valores actuales por defecto)
 * y la sede puede sobrescribirlo (nullable = usar el de la federación):
 *   - clases_gracia_morosidad: clases de gracia antes del bloqueo por deuda (3).
 *   - exencion_matricula_desde_mes / _hasta_mes: ventana de exención de matrícula
 *     del alumno nuevo (octubre del año anterior → enero: 10 y 1).
 *   - dia_vencimiento_maximo: día máximo para el vencimiento de la mensualidad (20).
 *
 * Además, tipos_cargo gana un código estable para no depender del nombre literal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('federaciones', function (Blueprint $table) {
            $table->unsignedTinyInteger('clases_gracia_morosidad')->default(3)->after('activo');
            $table->unsignedTinyInteger('exencion_matricula_desde_mes')->default(10)->after('clases_gracia_morosidad');
            $table->unsignedTinyInteger('exencion_matricula_hasta_mes')->default(1)->after('exencion_matricula_desde_mes');
            $table->unsignedTinyInteger('dia_vencimiento_maximo')->default(20)->after('exencion_matricula_hasta_mes');
        });

        Schema::table('sedes', function (Blueprint $table) {
            $table->unsignedTinyInteger('clases_gracia_morosidad')->nullable()->after('dia_vencimiento_maximo');
            $table->unsignedTinyInteger('exencion_matricula_desde_mes')->nullable()->after('clases_gracia_morosidad');
            $table->unsignedTinyInteger('exencion_matricula_hasta_mes')->nullable()->after('exencion_matricula_desde_mes');
            $table->unsignedTinyInteger('dia_vencimiento_maximo')->nullable()->after('exencion_matricula_hasta_mes');
        });

        Schema::table('tipos_cargo', function (Blueprint $table) {
            // Código estable de uso (p. ej. 'matricula', 'mensualidad'); no depende
            // del nombre visible, que la federación puede renombrar.
            $table->string('codigo')->nullable()->after('nombre');
            $table->index(['federacion_id', 'codigo']);
        });

        // Marca los tipos existentes por su nombre actual (una sola vez).
        DB::table('tipos_cargo')->where('nombre', 'Matrícula')->update(['codigo' => 'matricula']);
        DB::table('tipos_cargo')->where('nombre', 'Mensualidad')->update(['codigo' => 'mensualidad']);
    }

    public function down(): void
    {
        Schema::table('tipos_cargo', function (Blueprint $table) {
            $table->dropIndex(['federacion_id', 'codigo']);
            $table->dropColumn('codigo');
        });

        Schema::table('sedes', function (Blueprint $table) {
            $table->dropColumn([
                'clases_gracia_morosidad',
                'exencion_matricula_desde_mes',
                'exencion_matricula_hasta_mes',
                'dia_vencimiento_maximo',
            ]);
        });

        Schema::table('federaciones', function (Blueprint $table) {
            $table->dropColumn([
                'clases_gracia_morosidad',
                'exencion_matricula_desde_mes',
                'exencion_matricula_hasta_mes',
                'dia_vencimiento_maximo',
            ]);
        });
    }
};
