<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notas del instructor sobre una matrícula (alumno), mostradas en la ficha. Dato
 * operativo del grupo. El autor es la cuenta que la registró.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notas_matricula', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_id')->constrained('grupos')->cascadeOnDelete();
            $table->foreignId('matricula_id')->constrained('matriculas')->cascadeOnDelete();
            $table->foreignId('autor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cuerpo');
            $table->timestamps();

            $table->index('matricula_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notas_matricula');
    }
};
