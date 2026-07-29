<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enlace técnica ↔ cinturón: qué técnicas del currículo corresponden a cada
 * grado. Catálogo compartido (ambas tablas son transversales). Una técnica de un
 * color se enlaza al grado de ese color en cada escala.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grado_tecnica', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grado_id')->constrained('grados')->cascadeOnDelete();
            $table->foreignId('tecnica_id')->constrained('tecnicas')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['grado_id', 'tecnica_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grado_tecnica');
    }
};
