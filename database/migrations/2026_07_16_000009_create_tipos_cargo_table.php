<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rediseño Fase 3: tipos de cargo de la federación (catálogo configurable de
 * cobros): matrícula, mensualidad, examen de grado, torneos, seminarios,
 * campamentos, otras actividades. `recurrente` marca los que se generan período
 * a período; `requiere_periodo` los que necesitan un mes de referencia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_cargo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('federacion_id')->constrained('federaciones')->cascadeOnDelete();
            $table->string('nombre');
            $table->boolean('recurrente')->default(false);
            $table->boolean('requiere_periodo')->default(false);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['federacion_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_cargo');
    }
};
