<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración.
     *
     * Inscripción de un estudiante a una convocatoria: del grado actual al grado
     * al que postula. El instructor_id es a quién se le acredita la graduación
     * (por defecto, el instructor de la clase del estudiante) para el conteo en
     * cascada. Guarda el resultado, la nota (9.0–9.9) y el visto bueno.
     */
    public function up(): void
    {
        Schema::create('inscripciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_id')->constrained('grupos')->cascadeOnDelete();
            $table->foreignId('convocatoria_id')->constrained('convocatorias')->cascadeOnDelete();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->foreignId('grado_origen_id')->nullable()->constrained('grados')->nullOnDelete();
            $table->foreignId('grado_destino_id')->nullable()->constrained('grados')->nullOnDelete();
            $table->foreignId('instructor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('visto_bueno')->default(false);
            $table->string('resultado')->nullable(); // App\Enums\ResultadoExamen
            $table->decimal('nota', 3, 1)->nullable(); // 9.0–9.9
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique(['convocatoria_id', 'estudiante_id']);
            $table->index('grupo_id');
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('inscripciones');
    }
};
