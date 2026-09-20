<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Instructor acreditado de la graduación (rediseño del crédito de graduación).
 *
 * - graduaciones.instructor_acreditado_persona_id (persona): quien recibe el
 *   crédito, resuelto por las reglas de origen (sede/matrícula), DISTINTO del
 *   examinador. Se migra el antiguo instructor_id (users) a la persona de ese
 *   usuario y se retira la columna: el crédito ya no cuelga de una cuenta.
 * - inscripciones: se retira el único (convocatoria_id, matricula_id) — un
 *   alumno reprobado puede volver a rendir en la misma convocatoria.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('graduaciones', function (Blueprint $table) {
            $table->foreignId('instructor_acreditado_persona_id')->nullable()->after('instructor_id')
                ->constrained('personas')->nullOnDelete();
        });

        // Migra el instructor acreditado (users) a su persona.
        DB::statement(
            'UPDATE graduaciones SET instructor_acreditado_persona_id = '
            .'(SELECT persona_id FROM users WHERE users.id = graduaciones.instructor_id) '
            .'WHERE instructor_id IS NOT NULL'
        );

        Schema::table('graduaciones', function (Blueprint $table) {
            $table->dropIndex(['instructor_id']);
            $table->dropConstrainedForeignId('instructor_id');
        });

        Schema::table('inscripciones', function (Blueprint $table) {
            $table->dropUnique(['convocatoria_id', 'matricula_id']);
        });
    }

    public function down(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->unique(['convocatoria_id', 'matricula_id']);
        });

        Schema::table('graduaciones', function (Blueprint $table) {
            $table->foreignId('instructor_id')->nullable()->after('grado_destino_id')
                ->constrained('users')->nullOnDelete();
            $table->index('instructor_id');
        });

        DB::statement(
            'UPDATE graduaciones SET instructor_id = '
            .'(SELECT id FROM users WHERE users.persona_id = graduaciones.instructor_acreditado_persona_id LIMIT 1) '
            .'WHERE instructor_acreditado_persona_id IS NOT NULL'
        );

        Schema::table('graduaciones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('instructor_acreditado_persona_id');
        });
    }
};
