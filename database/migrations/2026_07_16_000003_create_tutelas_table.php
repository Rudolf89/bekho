<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tutela: vínculo apoderado ↔ alumno. Puede cruzar grupos (un apoderado con
 * hijos en grupos distintos), por eso NO lleva grupo_id. Único por par. El
 * apoderado debe ser mayor de edad (se valida en la aplicación).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apoderado_persona_id')->constrained('personas')->cascadeOnDelete();
            $table->foreignId('alumno_persona_id')->constrained('personas')->cascadeOnDelete();
            $table->string('parentesco'); // App\Enums\Parentesco
            $table->boolean('responsable_pago')->default(false);
            $table->date('vigente_desde')->nullable();
            $table->date('vigente_hasta')->nullable();
            $table->timestamps();

            $table->unique(['apoderado_persona_id', 'alumno_persona_id']);
            $table->index('alumno_persona_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutelas');
    }
};
