<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rediseño Fase 4: los logros (gamificación) se otorgan por MATRÍCULA. Se añade
 * matricula_id con su índice (matrícula, recompensa).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logros', function (Blueprint $table) {
            $table->foreignId('matricula_id')->after('grupo_id')
                ->constrained('matriculas')->cascadeOnDelete();
            $table->index(['matricula_id', 'recompensa_id']);
        });
    }

    public function down(): void
    {
        Schema::table('logros', function (Blueprint $table) {
            $table->dropIndex(['matricula_id', 'recompensa_id']);
            $table->dropConstrainedForeignId('matricula_id');
        });
    }
};
