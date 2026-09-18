<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración.
     *
     * Ficha del alumno. Es DISTINTA del User: un estudiante puede o no tener
     * cuenta de login (user_id nullable). Los menores entran vía su apoderado.
     */
    public function up(): void
    {
        Schema::create('estudiantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_id')->constrained('grupos')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sede_id')->nullable()->constrained('sedes')->nullOnDelete();
            $table->foreignId('grado_id')->nullable()->constrained('grados')->nullOnDelete();
            $table->string('nombre');
            $table->string('rut')->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('grupo_etario'); // App\Enums\GrupoEtario (uno solo)
            $table->string('nivel'); // App\Enums\NivelEntrenamiento
            $table->string('telefono_contacto')->nullable();
            $table->string('email_contacto')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('grupo_id');
            $table->unique(['grupo_id', 'rut']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('estudiantes');
    }
};
