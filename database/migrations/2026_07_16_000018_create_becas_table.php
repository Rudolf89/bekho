<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rediseño Fase 5: becas y convenios por matrícula. Descuento en porcentaje o
 * monto fijo, con vigencia. Se aplica sobre el monto ya rebajado por tramo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('becas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_id')->constrained('grupos')->cascadeOnDelete();
            $table->foreignId('matricula_id')->constrained('matriculas')->cascadeOnDelete();
            $table->string('tipo'); // App\Enums\TipoBeca
            $table->unsignedInteger('valor'); // % (0-100) o monto en CLP
            $table->string('motivo')->nullable();
            $table->date('vigente_desde')->nullable();
            $table->date('vigente_hasta')->nullable();
            $table->timestamps();

            $table->index('matricula_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('becas');
    }
};
