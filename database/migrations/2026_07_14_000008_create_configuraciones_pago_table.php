<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración.
     *
     * Configuración de pagos POR academia (cada escuela tiene su propio valor).
     * Los montos quedan nullable: se cargan por academia, no se inventan.
     */
    public function up(): void
    {
        Schema::create('configuraciones_pago', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academia_id')->unique()->constrained('academias')->cascadeOnDelete();
            $table->unsignedInteger('valor_mensualidad')->nullable(); // CLP
            $table->unsignedInteger('valor_matricula')->nullable();    // CLP
            $table->unsignedTinyInteger('dia_vencimiento')->nullable();
            $table->unsignedTinyInteger('descuento_hermanos_pct')->default(20);
            $table->timestamps();
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuraciones_pago');
    }
};
