<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reconecta las lecciones de vida bajo su ciclo: la semana pasa a ser DENTRO del
 * ciclo (1–8) y la Habilidad para la Vida se deriva del ciclo. Antes la semana
 * era global y única; ahora es única por (ciclo, semana).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lecciones_vida', function (Blueprint $table) {
            $table->foreignId('ciclo_id')->nullable()->after('id')->constrained('ciclos')->cascadeOnDelete();
        });

        Schema::table('lecciones_vida', function (Blueprint $table) {
            $table->dropUnique(['semana']);
            $table->dropColumn('habilidad'); // se deriva del ciclo
            $table->unique(['ciclo_id', 'semana']);
        });
    }

    public function down(): void
    {
        Schema::table('lecciones_vida', function (Blueprint $table) {
            $table->dropUnique(['ciclo_id', 'semana']);
            $table->string('habilidad')->nullable();
            $table->dropConstrainedForeignId('ciclo_id');
            $table->unique('semana');
        });
    }
};
