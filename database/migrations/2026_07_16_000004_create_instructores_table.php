<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Instructor: faceta marcial de una persona (rango, supervisión y certificación).
 * Es transversal a la federación (sin grupo_id); la pertenencia a un grupo va en
 * personal_grupo. El supervisor debe ser del mismo grupo (se valida en la app).
 * fecha_certificacion desempata al aprobador de traslados por antigüedad.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instructores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id')->unique()->constrained('personas')->cascadeOnDelete();
            $table->foreignId('rango_id')->nullable()->constrained('cargos_rangos')->nullOnDelete();
            $table->foreignId('supervisor_persona_id')->nullable()->constrained('personas')->nullOnDelete();
            $table->date('fecha_certificacion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instructores');
    }
};
