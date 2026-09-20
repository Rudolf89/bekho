<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Planilla" quedaba usado para dos cosas: la planificación de clase y las
 * planillas de competencia. Se renombra la planificación de clase para reservar
 * "planilla" exclusivamente a competencia. Renombre puro (sin recrear tablas ni
 * perder datos):
 *   - planillas            → planificaciones_clase
 *   - bloques_planilla     → bloques_planificacion
 *   - cuadrantes_planilla  → cuadrantes_planificacion
 *   - *.planilla_id        → *.planificacion_clase_id (clases y las hijas)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('planillas', 'planificaciones_clase');
        Schema::rename('bloques_planilla', 'bloques_planificacion');
        Schema::rename('cuadrantes_planilla', 'cuadrantes_planificacion');

        Schema::table('clases', function (Blueprint $table) {
            $table->renameColumn('planilla_id', 'planificacion_clase_id');
        });
        Schema::table('bloques_planificacion', function (Blueprint $table) {
            $table->renameColumn('planilla_id', 'planificacion_clase_id');
        });
        Schema::table('cuadrantes_planificacion', function (Blueprint $table) {
            $table->renameColumn('planilla_id', 'planificacion_clase_id');
        });
    }

    public function down(): void
    {
        Schema::table('clases', function (Blueprint $table) {
            $table->renameColumn('planificacion_clase_id', 'planilla_id');
        });
        Schema::table('cuadrantes_planificacion', function (Blueprint $table) {
            $table->renameColumn('planificacion_clase_id', 'planilla_id');
        });
        Schema::table('bloques_planificacion', function (Blueprint $table) {
            $table->renameColumn('planificacion_clase_id', 'planilla_id');
        });

        Schema::rename('cuadrantes_planificacion', 'cuadrantes_planilla');
        Schema::rename('bloques_planificacion', 'bloques_planilla');
        Schema::rename('planificaciones_clase', 'planillas');
    }
};
