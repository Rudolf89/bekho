<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Revisión del examinador sobre un intento: el auto-puntaje es inmediato, pero
 * la aprobación final (o "volver a intentar") la decide el examinador, con
 * justificación cuando aprueba un intento que no alcanzó el umbral.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intentos_cuestionario', function (Blueprint $table) {
            $table->string('estado')->default('pendiente')->after('aprobado'); // App\Enums\EstadoIntento
            $table->foreignId('revisado_por')->nullable()->after('estado')->constrained('users')->nullOnDelete();
            $table->timestamp('revisado_at')->nullable()->after('revisado_por');
            $table->text('justificacion')->nullable()->after('revisado_at');
        });
    }

    public function down(): void
    {
        Schema::table('intentos_cuestionario', function (Blueprint $table) {
            $table->dropConstrainedForeignId('revisado_por');
            $table->dropColumn(['estado', 'revisado_at', 'justificacion']);
        });
    }
};
