<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inscripción enriquecida: apto médico y consentimiento de uso de imagen (datos
 * de la matrícula, capturados en el alta), más observaciones médicas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matriculas', function (Blueprint $table) {
            $table->boolean('apto_medico')->default(false)->after('acepto_reglamento_at');
            $table->text('observaciones_medicas')->nullable()->after('apto_medico');
            $table->boolean('autoriza_imagen')->default(false)->after('observaciones_medicas');
        });
    }

    public function down(): void
    {
        Schema::table('matriculas', function (Blueprint $table) {
            $table->dropColumn(['apto_medico', 'observaciones_medicas', 'autoriza_imagen']);
        });
    }
};
