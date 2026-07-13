<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración.
     *
     * Inscripción N–M del estudiante en programas (además de su grupo etario).
     */
    public function up(): void
    {
        Schema::create('estudiante_programa', function (Blueprint $table) {
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->foreignId('programa_id')->constrained('programas')->cascadeOnDelete();

            $table->primary(['estudiante_id', 'programa_id']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('estudiante_programa');
    }
};
