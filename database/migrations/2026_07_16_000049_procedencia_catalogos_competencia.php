<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Procedencia de los catálogos de competencia. Los grupos de edad, las
 * categorías y la tabla de libres pasan a cargarse desde la planilla de
 * competencia oficial de la federación, así que llevan `fuente`/`verificado`
 * como el resto del contenido (ver "Procedencia del contenido" en CLAUDE.md).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Tabla por tabla (sin bucle) para que el análisis estático vea las columnas.
        Schema::table('grupos_edad', function (Blueprint $table) {
            $table->string('fuente')->nullable();
            $table->boolean('verificado')->default(false);
        });

        Schema::table('categorias_competencia', function (Blueprint $table) {
            $table->string('fuente')->nullable();
            $table->boolean('verificado')->default(false);
        });

        Schema::table('tabla_libres', function (Blueprint $table) {
            $table->string('fuente')->nullable();
            $table->boolean('verificado')->default(false);
        });
    }

    public function down(): void
    {
        foreach (['grupos_edad', 'categorias_competencia', 'tabla_libres'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropColumn(['fuente', 'verificado']);
            });
        }
    }
};
