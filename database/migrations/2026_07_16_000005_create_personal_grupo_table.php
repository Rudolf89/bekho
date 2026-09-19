<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * personal_grupo: una persona que trabaja en un grupo (dirección, recepción,
 * instructor, etc.). Dato operativo del grupo (grupo_id + PerteneceGrupo).
 * Los roles concretos por grupo/sede vivirán en personal_grupo_rol (Fase 7).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_grupo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id')->constrained('personas')->cascadeOnDelete();
            $table->foreignId('grupo_id')->constrained('grupos')->cascadeOnDelete();
            $table->boolean('activo')->default(true);
            $table->date('fecha_ingreso')->nullable();
            $table->timestamps();

            $table->unique(['persona_id', 'grupo_id']);
            $table->index('grupo_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_grupo');
    }
};
