<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración.
     *
     * Bloques de actividad de una planilla (línea de tiempo ~45 min). El contenido
     * es el que corresponde al grupo etario de la planilla.
     */
    public function up(): void
    {
        Schema::create('bloques_planilla', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academia_id')->constrained('academias')->cascadeOnDelete();
            $table->foreignId('planilla_id')->constrained('planillas')->cascadeOnDelete();
            $table->string('tipo'); // App\Enums\TipoBloque
            $table->text('contenido')->nullable();
            $table->smallInteger('orden')->default(0);
            $table->timestamps();

            $table->index('academia_id');
            $table->index(['planilla_id', 'orden']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('bloques_planilla');
    }
};
