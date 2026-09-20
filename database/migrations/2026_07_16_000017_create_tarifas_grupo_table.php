<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rediseño Fase 5: tarifas por grupo y tipo de cargo, con tramos por cantidad de
 * alumnos de la familia (p. ej. 1 alumno un monto, 2+ otro). Cada grupo define
 * las suyas. `cantidad_alumnos` = tamaño de familia desde el que aplica el monto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarifas_grupo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_id')->constrained('grupos')->cascadeOnDelete();
            $table->foreignId('tipo_cargo_id')->constrained('tipos_cargo')->cascadeOnDelete();
            $table->unsignedSmallInteger('cantidad_alumnos')->default(1); // tramo (familia)
            $table->unsignedInteger('monto_por_alumno');
            $table->date('vigente_desde')->nullable();
            $table->timestamps();

            $table->index(['grupo_id', 'tipo_cargo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarifas_grupo');
    }
};
