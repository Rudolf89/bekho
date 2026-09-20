<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rediseño Fase 3: escalas de puntaje de la federación. Competencia: 9.1 a 9.9
 * con paso 0.1. Rúbrica de evaluación: 0 a 6.0. Catálogo configurable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escalas_puntaje', function (Blueprint $table) {
            $table->id();
            $table->foreignId('federacion_id')->constrained('federaciones')->cascadeOnDelete();
            $table->string('nombre');
            $table->decimal('minimo', 5, 2);
            $table->decimal('maximo', 5, 2);
            $table->decimal('paso', 5, 2);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['federacion_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escalas_puntaje');
    }
};
