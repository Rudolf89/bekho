<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración.
     *
     * Cada clase del horario apunta a la planilla que le corresponde, para que el
     * instructor abra la clase del día y vea su rutina.
     */
    public function up(): void
    {
        Schema::table('clases', function (Blueprint $table) {
            $table->foreignId('planilla_id')->nullable()->after('instructor_id')
                ->constrained('planillas')->nullOnDelete();
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::table('clases', function (Blueprint $table) {
            $table->dropForeign(['planilla_id']);
            $table->dropColumn('planilla_id');
        });
    }
};
