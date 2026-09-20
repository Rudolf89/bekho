<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Los ciclos apuntan a la habilidad para la vida del catálogo del manual
 * (habilidades_vida) por FK, además de la clave enum histórica. Nullable: si el
 * catálogo aún no está sembrado, el ciclo queda sin enlazar (no falla).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ciclos', function (Blueprint $table) {
            $table->foreignId('habilidad_vida_id')->nullable()->after('habilidad_vida')
                ->constrained('habilidades_vida')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ciclos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('habilidad_vida_id');
        });
    }
};
