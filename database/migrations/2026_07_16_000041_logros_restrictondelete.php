<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Regla "sin cascadas sobre el historial": los logros (gamificación) son historial
 * y no deben desaparecer al borrar en duro una matrícula. Se pasa
 * logros.matricula_id de cascadeOnDelete a restrictOnDelete (quedó fuera del lote
 * 000040 por omisión). El drop y la recreación van en pasadas separadas porque
 * SQLite reconstruye la tabla al tocar una FK.
 *
 * Reversible: down() vuelve a cascadeOnDelete.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logros', function (Blueprint $table) {
            $table->dropForeign(['matricula_id']);
        });

        Schema::table('logros', function (Blueprint $table) {
            $table->foreign('matricula_id')->references('id')->on('matriculas')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('logros', function (Blueprint $table) {
            $table->dropForeign(['matricula_id']);
        });

        Schema::table('logros', function (Blueprint $table) {
            $table->foreign('matricula_id')->references('id')->on('matriculas')->cascadeOnDelete();
        });
    }
};
