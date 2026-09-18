<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Horarios de una clase: día + hora de inicio y fin. Una clase de lunes y
 * miércoles es UNA clase con dos horarios. La hora de fin es obligatoria: se
 * usa para calcular las horas de programa. Único por clase, día y hora.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horarios_clase', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clase_id')->constrained('clases')->cascadeOnDelete();
            $table->unsignedTinyInteger('dia_semana'); // App\Enums\DiaSemana (1-7)
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->timestamps();

            $table->unique(['clase_id', 'dia_semana', 'hora_inicio']);
            $table->index('dia_semana');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios_clase');
    }
};
