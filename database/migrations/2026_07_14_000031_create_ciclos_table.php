<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ciclo: la columna vertebral del currículo ATA. El manual organiza TODO en 6
 * ciclos, uno por Habilidad para la Vida Songahm (Disciplina, Convicción,
 * Comunicación, Respeto, Autoestima, Honestidad), cada uno de 8 semanas. De cada
 * ciclo cuelgan las lecciones de vida (y, más adelante, los class planners).
 *
 * Catálogo compartido (contenido ATA) → SIN grupo_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ciclos', function (Blueprint $table) {
            $table->id();
            $table->string('habilidad_vida')->unique(); // App\Enums\HabilidadVida
            $table->string('nombre');
            $table->unsignedInteger('orden')->default(0);
            $table->unsignedInteger('semanas')->default(8);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ciclos');
    }
};
