<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Biblioteca de calentamiento (catálogo compartido ATA/BEKHO, igual para todas
 * los grupos → SIN grupo_id, como cargos_rangos).
 *
 * - `categorias_calentamiento`: las 6 categorías (con color y orden).
 * - `categoria_calentamiento_grupo`: qué categorías aplican a cada grupo etario
 *   (los Tigers no hacen combinaciones de puños ni cardio; For Kids no hace
 *   cardio).
 * - `ejercicios_calentamiento`: los ejercicios de cada categoría (nombre + desc).
 * - `notas_calentamiento`: la nota por grupo etario (ajustes específicos).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias_calentamiento', function (Blueprint $table) {
            $table->id();
            $table->string('clave')->unique(); // p. ej. "guardia"
            $table->string('nombre');
            $table->string('color')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('categoria_calentamiento_grupo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_calentamiento_id')->constrained('categorias_calentamiento')->cascadeOnDelete();
            $table->string('grupo_etario'); // App\Enums\GrupoEtario
            $table->timestamps();

            $table->unique(['categoria_calentamiento_id', 'grupo_etario'], 'cat_calent_grupo_unico');
        });

        Schema::create('ejercicios_calentamiento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_calentamiento_id')->constrained('categorias_calentamiento')->cascadeOnDelete();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('notas_calentamiento', function (Blueprint $table) {
            $table->id();
            $table->string('grupo_etario')->unique(); // App\Enums\GrupoEtario
            $table->text('nota');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notas_calentamiento');
        Schema::dropIfExists('ejercicios_calentamiento');
        Schema::dropIfExists('categoria_calentamiento_grupo');
        Schema::dropIfExists('categorias_calentamiento');
    }
};
