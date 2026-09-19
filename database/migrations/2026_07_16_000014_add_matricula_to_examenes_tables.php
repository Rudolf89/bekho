<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rediseño Fase 4: las inscripciones a exámenes y las graduaciones se registran
 * por MATRÍCULA. Se añade matricula_id a ambas (con único e índice).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->foreignId('matricula_id')->after('convocatoria_id')
                ->constrained('matriculas')->cascadeOnDelete();
            $table->unique(['convocatoria_id', 'matricula_id']);
        });

        Schema::table('graduaciones', function (Blueprint $table) {
            $table->foreignId('matricula_id')->after('grupo_id')
                ->constrained('matriculas')->cascadeOnDelete();
            $table->index('matricula_id');
        });
    }

    public function down(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->dropUnique(['convocatoria_id', 'matricula_id']);
            $table->dropConstrainedForeignId('matricula_id');
        });

        Schema::table('graduaciones', function (Blueprint $table) {
            $table->dropIndex(['matricula_id']);
            $table->dropConstrainedForeignId('matricula_id');
        });
    }
};
