<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Juramentos ATA que se recitan en clase (Espíritu Songahm y juramento Tigers).
 * Catálogo de la federación (sin grupo_id), con procedencia (fuente/verificado).
 * categoria_clase = App\Enums\CategoriaJuramento (Tigers / Kids y Adultos);
 * momento = App\Enums\MomentoJuramento (inicio / cierre / ambos).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('juramentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('federacion_id')->constrained('federaciones')->restrictOnDelete();
            $table->string('categoria_clase'); // App\Enums\CategoriaJuramento
            $table->string('momento');         // App\Enums\MomentoJuramento
            $table->string('nombre');
            $table->text('texto');
            $table->unsignedInteger('orden')->default(0);
            $table->string('fuente')->nullable();
            $table->boolean('verificado')->default(false);
            $table->timestamps();

            $table->unique(['federacion_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('juramentos');
    }
};
