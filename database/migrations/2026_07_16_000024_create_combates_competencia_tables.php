<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 6b (sparring): la parte de combate de la planilla de competencia. Combates
 * entre competidores (o libre, sin rival), marcas por competidor (puntos,
 * advertencias, descalificación que marca el planillero), resultados con el lugar
 * y el recuento de medallas de la planilla.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('combates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planilla_id')->constrained('planillas_competencia')->cascadeOnDelete();
            $table->string('ronda')->nullable();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->foreignId('competidor_a_id')->constrained('competidores_planilla')->cascadeOnDelete();
            $table->foreignId('competidor_b_id')->nullable()->constrained('competidores_planilla')->nullOnDelete(); // null = libre
            $table->foreignId('ganador_id')->nullable()->constrained('competidores_planilla')->nullOnDelete();
            $table->string('tipo')->nullable(); // libre | eliminatoria | final | …
            $table->timestamps();

            $table->index('planilla_id');
        });

        Schema::create('marcas_combate', function (Blueprint $table) {
            $table->id();
            $table->foreignId('combate_id')->constrained('combates')->cascadeOnDelete();
            $table->foreignId('competidor_planilla_id')->constrained('competidores_planilla')->cascadeOnDelete();
            $table->unsignedSmallInteger('puntos')->default(0);
            $table->unsignedSmallInteger('advertencias')->default(0);
            $table->boolean('descalificado')->default(false);
            $table->timestamps();

            $table->unique(['combate_id', 'competidor_planilla_id']);
        });

        Schema::create('resultados_planilla', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planilla_id')->constrained('planillas_competencia')->cascadeOnDelete();
            $table->unsignedSmallInteger('lugar');
            $table->foreignId('competidor_planilla_id')->constrained('competidores_planilla')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['planilla_id', 'competidor_planilla_id']);
        });

        Schema::create('recuento_medallas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planilla_id')->unique()->constrained('planillas_competencia')->cascadeOnDelete();
            $table->unsignedSmallInteger('primer_lugar')->default(0);
            $table->unsignedSmallInteger('segundo_lugar')->default(0);
            $table->unsignedSmallInteger('tercer_lugar')->default(0);
            $table->unsignedSmallInteger('participacion')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recuento_medallas');
        Schema::dropIfExists('resultados_planilla');
        Schema::dropIfExists('marcas_combate');
        Schema::dropIfExists('combates');
    }
};
