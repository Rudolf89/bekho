<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tipo de convocatoria (App\Enums\TipoConvocatoria). Por defecto 'instructor',
 * que conserva el comportamiento actual: la nota (escala 9.1–9.9, aprobación
 * 9.5) solo se valida en las convocatorias de instructor.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('convocatorias', function (Blueprint $table) {
            $table->string('tipo')->default('instructor')->after('nombre');
        });
    }

    public function down(): void
    {
        Schema::table('convocatorias', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
    }
};
