<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración.
     *
     * Relación N–M apoderado ↔ estudiante. El apoderado es un User (rol
     * apoderado) que inicia sesión para ver a sus hijos; un apoderado puede
     * tener varios hijos y un estudiante varios apoderados.
     */
    public function up(): void
    {
        Schema::create('apoderado_estudiante', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();

            $table->primary(['user_id', 'estudiante_id']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('apoderado_estudiante');
    }
};
