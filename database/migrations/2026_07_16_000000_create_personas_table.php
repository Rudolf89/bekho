<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rediseño Fase 2: la PERSONA es la identidad única de la federación (por
 * encima del grupo). Una persona puede matricularse en varios grupos a lo
 * largo del tiempo, ser apoderada, personal o instructora: por eso NO lleva
 * grupo_id. Fecha de nacimiento obligatoria (la regla de menores depende de
 * ella). Sin fotografía ni datos de salud. grado_id es caché de la última
 * graduación.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personas', function (Blueprint $table) {
            $table->id();
            $table->string('nombres');
            $table->string('apellido_paterno')->nullable();
            $table->string('apellido_materno')->nullable();
            $table->date('fecha_nacimiento');
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->string('direccion')->nullable();
            $table->string('comuna')->nullable();
            $table->string('region')->nullable();
            $table->string('contacto_emergencia_nombre')->nullable();
            $table->string('contacto_emergencia_telefono')->nullable();
            $table->string('contacto_emergencia_relacion')->nullable();
            $table->foreignId('grado_id')->nullable()->constrained('grados')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personas');
    }
};
