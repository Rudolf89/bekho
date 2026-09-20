<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rediseño Fase 7: auditoría de las CONSULTAS de datos entre grupos (lo que el
 * paquete de auditoría de cambios no cubre). Registra cada búsqueda de una
 * persona por documento: quién la hizo, desde qué grupo, qué documento y con qué
 * resultado. Guarda copia de dato personal → sujeto a política de retención.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accesos_datos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('grupo_id')->nullable()->constrained('grupos')->nullOnDelete();
            $table->string('documento_consultado');
            $table->string('resultado'); // App\Enums\ResultadoBusqueda
            $table->timestamps();

            $table->index('user_id');
            $table->index('grupo_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accesos_datos');
    }
};
