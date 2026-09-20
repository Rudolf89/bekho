<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Biblioteca de técnicas: catálogo transversal del currículo técnico ATA
 * (patadas, formas, manos, tricks, armas, rompimientos, protech), etiquetado por
 * cinturón/nivel, modalidad (tradicional/creative/xtreme) y core-vs-electivo.
 *
 * Catálogo compartido → SIN grupo_id. Las técnicas con secuencia (formas,
 * segmentos de armas, combinaciones de manos) guardan sus pasos en pasos_tecnica.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tecnicas', function (Blueprint $table) {
            $table->id();
            $table->string('categoria');            // App\Enums\CategoriaTecnica
            $table->string('subcategoria')->nullable(); // p. ej. creative/pop/cheat, o el arma
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('modalidad')->nullable(); // App\Enums\ModalidadTecnica
            $table->string('cinturon')->nullable();  // etiqueta de cinturón/dan
            $table->string('nivel')->nullable();     // App\Enums\NivelEntrenamiento
            $table->boolean('core')->default(true);  // core vs electivo
            $table->string('significado')->nullable(); // para formas
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();

            $table->index(['categoria', 'orden']);
        });

        Schema::create('pasos_tecnica', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tecnica_id')->constrained('tecnicas')->cascadeOnDelete();
            $table->string('segmento')->nullable(); // "Segmento 1", "Combo 1"…
            $table->unsignedInteger('orden')->default(0);
            $table->text('texto');
            $table->timestamps();

            $table->index(['tecnica_id', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pasos_tecnica');
        Schema::dropIfExists('tecnicas');
    }
};
