<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rediseño Fase 4: los pagos se registran por MATRÍCULA (no por estudiante).
 * Se añade matricula_id con su índice único (matrícula, tipo, período).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->foreignId('matricula_id')->after('grupo_id')
                ->constrained('matriculas')->cascadeOnDelete();
            $table->unique(['matricula_id', 'tipo', 'periodo']);
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropUnique(['matricula_id', 'tipo', 'periodo']);
            $table->dropConstrainedForeignId('matricula_id');
        });
    }
};
