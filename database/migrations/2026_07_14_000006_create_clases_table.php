<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración.
     *
     * Clase recurrente: sede + grupo etario + instructores. Los días y horas
     * viven en horarios_clase (una clase de lunes y miércoles = una clase con
     * dos horarios). La FK compuesta (sede_id, grupo_id) hacia sedes garantiza
     * que la clase pertenezca al mismo grupo que su sede.
     */
    public function up(): void
    {
        Schema::create('clases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_id')->constrained('grupos')->cascadeOnDelete();
            $table->foreignId('sede_id');
            $table->foreignId('instructor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nombre');
            $table->string('grupo_etario'); // App\Enums\GrupoEtario
            $table->unsignedInteger('cupo_maximo')->nullable(); // aforo de la clase (solo advierte)
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('grupo_id');
            $table->index('sede_id');
            $table->foreign(['sede_id', 'grupo_id'])
                ->references(['id', 'grupo_id'])->on('sedes')->cascadeOnDelete();
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
