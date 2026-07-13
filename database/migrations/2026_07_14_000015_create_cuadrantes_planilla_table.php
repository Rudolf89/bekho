<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración.
     *
     * Capa de Cuadrantes de Enseñanza de una planilla (Estructura, Emoción,
     * Conocimiento, Legado), como lista de verificación con una nota por cuadrante.
     */
    public function up(): void
    {
        Schema::create('cuadrantes_planilla', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academia_id')->constrained('academias')->cascadeOnDelete();
            $table->foreignId('planilla_id')->constrained('planillas')->cascadeOnDelete();
            $table->string('cuadrante'); // App\Enums\Cuadrante
            $table->text('nota')->nullable();
            $table->timestamps();

            $table->unique(['planilla_id', 'cuadrante']);
            $table->index('academia_id');
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuadrantes_planilla');
    }
};
