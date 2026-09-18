<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Programa Legacy operativo: track de formación de instructores (Niveles 1-3,
 * 100 h cada uno, requisitos y ascenso).
 *
 * Catálogo compartido (sin grupo_id): niveles_legacy y requisitos_legacy.
 * Operativo (con grupo_id): inscripciones_legacy, horas_legacy y el
 * cumplimiento de requisitos por inscripción.
 */
return new class extends Migration
{
    public function up(): void
    {
        // --- Catálogo compartido -------------------------------------------------
        Schema::create('niveles_legacy', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->unsignedInteger('orden')->default(0);
            $table->unsignedInteger('horas_requeridas')->default(100);
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });

        Schema::create('requisitos_legacy', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nivel_legacy_id')->constrained('niveles_legacy')->cascadeOnDelete();
            $table->string('texto');
            // Si se enlaza a un cuestionario, el requisito se cumple solo cuando el
            // usuario tiene un intento aprobado de ese cuestionario (prueba escrita).
            $table->foreignId('cuestionario_id')->nullable()->constrained('cuestionarios')->nullOnDelete();
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });

        // --- Operativo (con grupo_id) ----------------------------------------
        Schema::create('inscripciones_legacy', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_id')->nullable()->constrained('grupos')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('nivel_legacy_id')->constrained('niveles_legacy')->cascadeOnDelete();
            $table->string('estado')->default('en_curso'); // App\Enums\EstadoLegacy
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_aprobacion')->nullable();
            $table->foreignId('aprobado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->text('nota')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'nivel_legacy_id']);
        });

        Schema::create('horas_legacy', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscripcion_legacy_id')->constrained('inscripciones_legacy')->cascadeOnDelete();
            $table->date('fecha');
            $table->decimal('horas', 6, 2)->default(0);
            $table->string('descripcion')->nullable();
            $table->foreignId('verificado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('cumplimiento_requisitos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscripcion_legacy_id')->constrained('inscripciones_legacy')->cascadeOnDelete();
            $table->foreignId('requisito_legacy_id')->constrained('requisitos_legacy')->cascadeOnDelete();
            $table->foreignId('verificado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verificado_at')->nullable();
            $table->timestamps();

            $table->unique(['inscripcion_legacy_id', 'requisito_legacy_id'], 'cumplimiento_unico');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cumplimiento_requisitos');
        Schema::dropIfExists('horas_legacy');
        Schema::dropIfExists('inscripciones_legacy');
        Schema::dropIfExists('requisitos_legacy');
        Schema::dropIfExists('niveles_legacy');
    }
};
