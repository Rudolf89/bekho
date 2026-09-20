<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class planner de un ciclo (grilla del manual ATA): por cada fila
 * (Warm-Up/Kicks/Forms/Quadrants/Protech/Drills) y bloque de semanas (1&2…7&8),
 * el contenido a trabajar. Catálogo compartido (cuelga del ciclo).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planner_ciclo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ciclo_id')->constrained('ciclos')->cascadeOnDelete();
            $table->string('fila');   // App\Enums\FilaPlannerCiclo
            $table->string('bloque'); // 1&2, 3&4, 5&6, 7&8
            $table->text('contenido')->nullable();
            $table->timestamps();

            $table->unique(['ciclo_id', 'fila', 'bloque'], 'planner_ciclo_unico');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planner_ciclo');
    }
};
