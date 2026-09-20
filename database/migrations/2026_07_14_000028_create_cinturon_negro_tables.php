<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Planificador de Cinturón Negro. NO se organiza por nivel sino por bloques de
 * semanas con un tema (Velocidad/Explosión, Defensa/Contraataque, …). Cada bloque
 * trae varias secciones (Warm Up General/Específico, Básicos, Sparring, Anuncios)
 * y una adaptación por grupo etario.
 *
 * Catálogo compartido → SIN grupo_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planificaciones_cinturon_negro', function (Blueprint $table) {
            $table->id();
            $table->string('clave')->unique(); // p. ej. "s12"
            $table->string('label');           // "Sem. 1 & 2"
            $table->string('tema');            // "Velocidad / Explosión"
            $table->string('icono')->nullable();
            $table->string('color')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('secciones_cinturon_negro', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planificacion_cinturon_negro_id')->constrained('planificaciones_cinturon_negro')->cascadeOnDelete();
            // warmup_general | warmup_especifico | basicos | sparring | anuncios
            $table->string('seccion');
            $table->text('item');
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('adaptaciones_cinturon_negro', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planificacion_cinturon_negro_id')->constrained('planificaciones_cinturon_negro')->cascadeOnDelete();
            $table->string('grupo_etario'); // App\Enums\GrupoEtario
            $table->text('texto');
            $table->timestamps();

            $table->unique(['planificacion_cinturon_negro_id', 'grupo_etario'], 'adapt_cn_grupo_unico');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adaptaciones_cinturon_negro');
        Schema::dropIfExists('secciones_cinturon_negro');
        Schema::dropIfExists('planificaciones_cinturon_negro');
    }
};
