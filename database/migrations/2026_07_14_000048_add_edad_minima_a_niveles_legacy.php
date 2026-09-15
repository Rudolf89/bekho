<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración.
     *
     * Cada nivel del Programa Legacy tiene una edad mínima de ascenso según el
     * Manual Legacy: Nivel 1 → 13 años, Nivel 2 → 16, Nivel 3 → 18.
     */
    public function up(): void
    {
        Schema::table('niveles_legacy', function (Blueprint $table) {
            $table->unsignedTinyInteger('edad_minima')->nullable()->after('horas_requeridas');
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::table('niveles_legacy', function (Blueprint $table) {
            $table->dropColumn('edad_minima');
        });
    }
};
