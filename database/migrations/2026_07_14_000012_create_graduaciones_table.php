<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración.
     *
     * Historial de graduaciones (solo aprobadas). Se crea al aplicar el resultado
     * de un examen aprobado y es la base del conteo en cascada por instructor_id.
     */
    public function up(): void
    {
        // matricula_id (y su índice) se añade en una migración posterior, porque
        // la tabla matriculas se crea después (rediseño Fase 2/4).
        Schema::create('graduaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_id')->constrained('grupos')->cascadeOnDelete();
            $table->foreignId('convocatoria_id')->nullable()->constrained('convocatorias')->nullOnDelete();
            $table->foreignId('grado_origen_id')->nullable()->constrained('grados')->nullOnDelete();
            $table->foreignId('grado_destino_id')->nullable()->constrained('grados')->nullOnDelete();
            $table->foreignId('instructor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('fecha');
            $table->string('resultado'); // App\Enums\ResultadoExamen (aprobado / con distinción)
            $table->decimal('nota', 3, 1)->nullable();
            $table->timestamps();

            $table->index('grupo_id');
            $table->index('instructor_id');
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('graduaciones');
    }
};
