<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración.
     *
     * Un rango puede abarcar un RANGO de grados Dan, no solo uno: el Profesor va
     * de 2º a 5º Dan. `grado_dan` es el piso y `grado_dan_max` el techo (null =
     * un único Dan, el de `grado_dan`).
     */
    public function up(): void
    {
        Schema::table('cargos_rangos', function (Blueprint $table) {
            $table->smallInteger('grado_dan_max')->nullable()->after('grado_dan');
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::table('cargos_rangos', function (Blueprint $table) {
            $table->dropColumn('grado_dan_max');
        });
    }
};
