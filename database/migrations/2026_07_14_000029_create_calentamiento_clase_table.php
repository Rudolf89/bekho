<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rutina de calentamiento guardada para una CLASE del horario: los ejercicios
 * que el instructor selecciona en "Armar calentamiento" quedan asociados a la
 * clase (dato operativo de cada grupo; el catálogo de ejercicios es
 * compartido). La clase ya lleva grupo_id, así que el aislamiento por tenant
 * se respeta por ahí.
 *
 * Las planillas son transversales (contenido ATA compartido), por eso la rutina
 * armada NO se cuelga de la planilla sino de la clase.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calentamiento_clase', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clase_id')->constrained('clases')->cascadeOnDelete();
            $table->foreignId('ejercicio_calentamiento_id')->constrained('ejercicios_calentamiento')->cascadeOnDelete();
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();

            $table->unique(['clase_id', 'ejercicio_calentamiento_id'], 'calent_clase_unico');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calentamiento_clase');
    }
};
