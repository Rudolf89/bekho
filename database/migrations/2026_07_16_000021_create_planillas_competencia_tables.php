<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rediseño Fase 6b: planillas operativas de competencia, usadas en las pruebas de
 * certificación de planillero (competidores y jueces ficticios, sin vínculo con
 * personas). Una planilla pertenece a una prueba y registra los puntajes de cada
 * competidor por criterio (escala 9.1–9.9 guardada en décimas 91–99; 0 = penal).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planillas_competencia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prueba_id')->constrained('pruebas')->cascadeOnDelete();
            $table->foreignId('grupo_edad_id')->nullable()->constrained('grupos_edad')->nullOnDelete();
            $table->foreignId('categoria_competencia_id')->nullable()->constrained('categorias_competencia')->nullOnDelete();
            $table->date('fecha')->nullable();
            $table->string('nro_pista')->nullable();
            $table->time('hora_inicio')->nullable();
            $table->time('hora_termino')->nullable();
            $table->string('genero')->nullable(); // masculino | femenino | mixto (texto libre)
            $table->string('estado')->default('borrador'); // App\Enums\EstadoPlanillaCompetencia
            $table->text('observaciones')->nullable();
            $table->foreignId('creado_por_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('jueces_planilla', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planilla_id')->constrained('planillas_competencia')->cascadeOnDelete();
            $table->string('papel'); // App\Enums\PapelJuezPlanilla (central | a | b | planillero)
            $table->string('nombre');
            $table->string('nivel_pais')->nullable();
            $table->timestamp('firmado_at')->nullable();
            $table->timestamps();
        });

        Schema::create('competidores_planilla', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planilla_id')->constrained('planillas_competencia')->cascadeOnDelete();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->string('nombre');
            $table->unsignedSmallInteger('edad')->nullable();
            $table->string('pais')->nullable();
            $table->timestamps();
        });

        Schema::create('puntajes_planilla', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competidor_planilla_id')->constrained('competidores_planilla')->cascadeOnDelete();
            $table->foreignId('criterio_prueba_id')->constrained('criterios_prueba')->cascadeOnDelete();
            $table->unsignedSmallInteger('puntaje'); // 91–99 (décimas) o 0 (penalización)
            $table->timestamps();

            $table->unique(['competidor_planilla_id', 'criterio_prueba_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('puntajes_planilla');
        Schema::dropIfExists('competidores_planilla');
        Schema::dropIfExists('jueces_planilla');
        Schema::dropIfExists('planillas_competencia');
    }
};
