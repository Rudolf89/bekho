<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rediseño Fase 4: la asistencia se registra por MATRÍCULA (no por estudiante).
 * Se añade matricula_id con su índice único (clase, matrícula, fecha). La tabla
 * matriculas ya existe a esta altura.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asistencias', function (Blueprint $table) {
            $table->foreignId('matricula_id')->after('clase_id')
                ->constrained('matriculas')->cascadeOnDelete();
            $table->unique(['clase_id', 'matricula_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropUnique(['clase_id', 'matricula_id', 'fecha']);
            $table->dropConstrainedForeignId('matricula_id');
        });
    }
};
