<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campos estructurados de un paso de forma (para mostrarlo como tabla en vez de
 * texto corrido): lado (Izq./Der./Ambos), postura (Frontal/Atrasada/Media…) y
 * sección/cuerpo (Alta/Media/Baja). Nulos en técnicas que no los usan (p. ej.
 * las secuencias de armas, que van solo con `texto`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pasos_tecnica', function (Blueprint $table) {
            $table->string('lado')->nullable()->after('segmento');
            $table->string('postura')->nullable()->after('lado');
            $table->string('seccion')->nullable()->after('postura');
        });
    }

    public function down(): void
    {
        Schema::table('pasos_tecnica', function (Blueprint $table) {
            $table->dropColumn(['lado', 'postura', 'seccion']);
        });
    }
};
