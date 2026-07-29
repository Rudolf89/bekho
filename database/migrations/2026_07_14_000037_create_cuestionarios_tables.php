<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Módulo de cuestionarios (evaluaciones autocorregidas). El CATÁLOGO es
 * transversal (contenido compartido por toda la federación, sin academia_id):
 * un examinador arma un cuestionario con sus preguntas y opciones. Los INTENTOS
 * son datos operativos (por usuario/academia).
 */
return new class extends Migration
{
    public function up(): void
    {
        // --- Catálogo compartido (sin academia_id) -------------------------------
        Schema::create('cuestionarios', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->string('area')->nullable(); // p. ej. "Arbitraje", "Currículo"
            $table->unsignedTinyInteger('umbral_aprobacion')->default(80); // % para aprobar
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('preguntas_cuestionario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuestionario_id')->constrained('cuestionarios')->cascadeOnDelete();
            $table->text('enunciado');
            $table->text('explicacion')->nullable(); // el "por qué" de la respuesta
            $table->text('nota')->nullable();         // aviso (p. ej. "verifica con tu instructor")
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('opciones_pregunta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pregunta_id')->constrained('preguntas_cuestionario')->cascadeOnDelete();
            $table->text('texto');
            $table->boolean('correcta')->default(false);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });

        // --- Intentos (operativo, con academia_id) -------------------------------
        Schema::create('intentos_cuestionario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academia_id')->nullable()->constrained('academias')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('cuestionario_id')->constrained('cuestionarios')->cascadeOnDelete();
            $table->unsignedInteger('correctas')->default(0);
            $table->unsignedInteger('total')->default(0);
            $table->unsignedTinyInteger('porcentaje')->default(0);
            $table->boolean('aprobado')->default(false);
            $table->timestamp('finalizado_at')->nullable();
            $table->timestamps();
        });

        Schema::create('respuestas_intento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intento_id')->constrained('intentos_cuestionario')->cascadeOnDelete();
            $table->foreignId('pregunta_id')->constrained('preguntas_cuestionario')->cascadeOnDelete();
            $table->boolean('correcta')->default(false);
            $table->timestamps();
            // Las opciones elegidas se guardan como pivote.
        });

        Schema::create('opcion_respuesta_intento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('respuesta_id')->constrained('respuestas_intento')->cascadeOnDelete();
            $table->foreignId('opcion_id')->constrained('opciones_pregunta')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opcion_respuesta_intento');
        Schema::dropIfExists('respuestas_intento');
        Schema::dropIfExists('intentos_cuestionario');
        Schema::dropIfExists('opciones_pregunta');
        Schema::dropIfExists('preguntas_cuestionario');
        Schema::dropIfExists('cuestionarios');
    }
};
