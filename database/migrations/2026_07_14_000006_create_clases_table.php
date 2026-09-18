<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración.
     *
     * Clase recurrente del horario: sede + grupo etario + nivel + día + hora e
     * instructor a cargo.
     */
    public function up(): void
    {
        Schema::create('clases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_id')->constrained('grupos')->cascadeOnDelete();
            $table->foreignId('sede_id')->constrained('sedes')->cascadeOnDelete();
            $table->foreignId('instructor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nombre');
            $table->string('grupo_etario'); // App\Enums\GrupoEtario
            $table->string('nivel'); // App\Enums\NivelEntrenamiento
            $table->unsignedTinyInteger('dia_semana'); // App\Enums\DiaSemana (1-7)
            $table->time('hora_inicio');
            $table->time('hora_fin')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('grupo_id');
            $table->index(['sede_id', 'dia_semana']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('clases');
    }
};
