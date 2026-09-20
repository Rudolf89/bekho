<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración.
     */
    public function up(): void
    {
        Schema::create('progreso_contenidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_id')->constrained('grupos')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('contenido_id')->constrained('contenidos')->cascadeOnDelete();
            $table->string('estado')->default('pendiente'); // App\Enums\EstadoProgreso
            $table->timestamp('visto_en')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'contenido_id']);
            $table->index('grupo_id');
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('progreso_contenidos');
    }
};
