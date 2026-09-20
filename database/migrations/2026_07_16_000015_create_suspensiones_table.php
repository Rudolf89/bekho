<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rediseño Fase 4: suspensión temporal de una matrícula (congelamiento). Durante
 * el período suspendido no se genera cargo ni se marca morosidad. Dato operativo
 * del grupo. `hasta` nulo = suspensión abierta (sin fecha de término).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suspensiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_id')->constrained('grupos')->cascadeOnDelete();
            $table->foreignId('matricula_id')->constrained('matriculas')->cascadeOnDelete();
            $table->date('desde');
            $table->date('hasta')->nullable();
            $table->string('motivo')->nullable();
            $table->foreignId('registrada_por_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('grupo_id');
            $table->index('matricula_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suspensiones');
    }
};
