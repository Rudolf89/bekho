<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inscripción enriquecida: consentimiento de uso de imagen (dato de la matrícula,
 * capturado en el alta).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matriculas', function (Blueprint $table) {
            $table->boolean('autoriza_imagen')->default(false)->after('acepto_reglamento_at');
        });
    }

    public function down(): void
    {
        Schema::table('matriculas', function (Blueprint $table) {
            $table->dropColumn('autoriza_imagen');
        });
    }
};
