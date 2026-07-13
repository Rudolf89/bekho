<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración.
     *
     * Registro de asistencia por estudiante, clase y fecha.
     */
    public function up(): void
    {
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academia_id')->constrained('academias')->cascadeOnDelete();
            $table->foreignId('clase_id')->constrained('clases')->cascadeOnDelete();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->date('fecha');
            $table->string('estado'); // App\Enums\EstadoAsistencia
            $table->timestamps();

            $table->unique(['clase_id', 'estudiante_id', 'fecha']);
            $table->index('academia_id');
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};
