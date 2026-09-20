<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Unificación LMS + Legacy — commit (c): instrumentos de evaluación práctica.
 *
 * Rúbricas con las que se evalúa a la persona en la práctica (prueba de
 * planillero, evaluación de formas y patadas…). El instrumento es catálogo de la
 * federación (puede acotarse a un programa/etapa); la evaluación es historial de
 * la persona (transversal, sin grupo_id, como graduaciones de formación).
 *
 * `umbral_porcentaje` y `nota_minima` son alternativos según la escala: el
 * planillero aprueba por porcentaje (80 % de 6.0); formas/patadas por nota mínima
 * (9.5 en la escala 9.1–9.9).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instrumentos_evaluacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('federacion_id')->constrained('federaciones')->restrictOnDelete();
            // Puede acotarse a un programa o etapa (nulo = instrumento suelto).
            $table->foreignId('programa_id')->nullable()->constrained('programas')->nullOnDelete();
            $table->foreignId('etapa_programa_id')->nullable()->constrained('etapas_programa')->nullOnDelete();
            $table->string('nombre');
            $table->foreignId('escala_id')->nullable()->constrained('escalas_puntaje')->restrictOnDelete();
            $table->decimal('puntaje_maximo', 5, 2)->nullable();
            // Alternativos: uno u otro según la escala.
            $table->unsignedTinyInteger('umbral_porcentaje')->nullable();
            $table->decimal('nota_minima', 5, 2)->nullable();
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('orden')->default(0);
            $table->string('fuente')->nullable();
            $table->boolean('verificado')->default(false);
            $table->timestamps();

            $table->unique(['federacion_id', 'nombre']);
        });

        Schema::create('secciones_instrumento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instrumento_evaluacion_id')->constrained('instrumentos_evaluacion')->cascadeOnDelete();
            $table->string('nombre');
            $table->decimal('ponderacion', 5, 2)->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();

            $table->index(['instrumento_evaluacion_id', 'orden']);
        });

        Schema::create('criterios_instrumento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seccion_instrumento_id')->constrained('secciones_instrumento')->cascadeOnDelete();
            $table->string('nombre');
            // Un criterio puede corresponder a un atributo técnico del catálogo.
            $table->foreignId('atributo_tecnico_id')->nullable()->constrained('atributos_tecnicos')->restrictOnDelete();
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();

            $table->index(['seccion_instrumento_id', 'orden']);
        });

        Schema::create('evaluaciones_practicas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instrumento_evaluacion_id')->constrained('instrumentos_evaluacion')->restrictOnDelete();
            $table->foreignId('persona_id')->constrained('personas')->restrictOnDelete();
            $table->foreignId('evaluador_persona_id')->nullable()->constrained('personas')->nullOnDelete();
            $table->date('fecha')->nullable();
            $table->decimal('puntaje_obtenido', 6, 2)->nullable();
            $table->decimal('porcentaje', 5, 2)->nullable();
            $table->boolean('aprobado')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('persona_id');
        });

        Schema::create('puntajes_criterio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluacion_practica_id')->constrained('evaluaciones_practicas')->cascadeOnDelete();
            $table->foreignId('criterio_instrumento_id')->constrained('criterios_instrumento')->cascadeOnDelete();
            $table->decimal('puntaje', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['evaluacion_practica_id', 'criterio_instrumento_id'], 'puntaje_criterio_unico');
        });

        // La planilla de competencia (certificación de planillero) se vincula a la
        // evaluación práctica que la respalda.
        Schema::table('planillas_competencia', function (Blueprint $table) {
            $table->foreignId('evaluacion_practica_id')->nullable()->after('id')
                ->constrained('evaluaciones_practicas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('planillas_competencia', function (Blueprint $table) {
            $table->dropConstrainedForeignId('evaluacion_practica_id');
        });

        Schema::dropIfExists('puntajes_criterio');
        Schema::dropIfExists('evaluaciones_practicas');
        Schema::dropIfExists('criterios_instrumento');
        Schema::dropIfExists('secciones_instrumento');
        Schema::dropIfExists('instrumentos_evaluacion');
    }
};
