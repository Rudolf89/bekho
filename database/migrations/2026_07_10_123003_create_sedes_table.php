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
        Schema::create('sedes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_id')->constrained('grupos')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('direccion')->nullable();
            $table->string('comuna')->nullable();
            $table->string('region')->nullable();
            $table->unsignedInteger('capacidad')->nullable(); // aforo de la sede
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('grupo_id');
            // Índice único compuesto para que las clases puedan referenciar
            // (sede_id, grupo_id) y así una clase nunca quede en otro grupo.
            $table->unique(['id', 'grupo_id']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('sedes');
    }
};
