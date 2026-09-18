<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Currículo por nivel (contenido técnico que rellena los bloques de la planilla):
 * fórmula, defensa, patadas, combinaciones de patadas (lista) y roturas.
 *
 * Catálogo compartido (currículo ATA) → SIN grupo_id. El prototipo cubre
 * Principiantes / Intermedio / Avanzado; Rojo-Negro y Danes quedan pendientes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curriculos_nivel', function (Blueprint $table) {
            $table->id();
            $table->string('nivel')->unique(); // App\Enums\NivelEntrenamiento
            $table->string('formula')->nullable();
            $table->string('defensa')->nullable();
            $table->text('patadas')->nullable();
            $table->json('combinaciones')->nullable(); // lista de strings
            $table->text('roturas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculos_nivel');
    }
};
