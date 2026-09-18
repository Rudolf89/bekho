<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lecciones de Vida (ATA Legacy). Cada lección corresponde a una semana y una
 * Habilidad para la Vida, y se presenta en 3 momentos de la clase (comienzo,
 * durante, fin), cada uno con un texto y una frase destacada.
 *
 * Catálogo compartido → SIN grupo_id. Se siembra la única cargada en el
 * prototipo (Semana 7 – Disciplina); el resto de las semanas queda pendiente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lecciones_vida', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('semana')->unique();
            $table->string('habilidad'); // App\Enums\HabilidadVida
            $table->text('comienzo_texto')->nullable();
            $table->string('comienzo_frase')->nullable();
            $table->text('durante_texto')->nullable();
            $table->string('durante_frase')->nullable();
            $table->text('fin_texto')->nullable();
            $table->string('fin_frase')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lecciones_vida');
    }
};
