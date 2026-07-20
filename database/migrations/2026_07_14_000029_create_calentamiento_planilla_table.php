<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rutina de calentamiento guardada para una planilla: los ejercicios que el
 * instructor selecciona en "Armar calentamiento" quedan asociados a la planilla
 * (el catálogo de ejercicios es compartido; la selección pertenece a la planilla,
 * que ya lleva academia_id, así que la costura de tenant se respeta por ahí).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calentamiento_planilla', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planilla_id')->constrained('planillas')->cascadeOnDelete();
            $table->foreignId('ejercicio_calentamiento_id')->constrained('ejercicios_calentamiento')->cascadeOnDelete();
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();

            $table->unique(['planilla_id', 'ejercicio_calentamiento_id'], 'calent_planilla_unico');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calentamiento_planilla');
    }
};
