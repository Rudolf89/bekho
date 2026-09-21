<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La rúbrica de la prueba de planillero sale del xlsx oficial de la planilla de
 * competencia, que trae dos cosas que el instrumento no sabía guardar:
 *
 *  - el ENUNCIADO del caso de cada sección (los participantes, la pista, la
 *    categoría y los incidentes de la competencia), y
 *  - el VALOR en puntos de cada ítem de la rúbrica (0,1 · 0,5 · 1,0), que suman
 *    el `puntaje_maximo` de 6,0 que declara la propia prueba.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('secciones_instrumento', function (Blueprint $table) {
            $table->text('enunciado')->nullable()->after('nombre');
        });

        Schema::table('criterios_instrumento', function (Blueprint $table) {
            $table->decimal('valor', 5, 2)->nullable()->after('nombre');
        });
    }

    public function down(): void
    {
        Schema::table('secciones_instrumento', function (Blueprint $table) {
            $table->dropColumn('enunciado');
        });

        Schema::table('criterios_instrumento', function (Blueprint $table) {
            $table->dropColumn('valor');
        });
    }
};
