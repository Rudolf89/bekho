<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentos de identidad de una persona (RUT, pasaporte). Único por tipo,
 * número y país: se conservan ambos si un extranjero con pasaporte obtiene
 * después un RUT. El RUT se valida (módulo 11) en la aplicación (App\Support\Rut).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos_persona', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id')->constrained('personas')->cascadeOnDelete();
            $table->string('tipo'); // App\Enums\TipoDocumento
            $table->string('numero');
            $table->string('pais', 2)->default('CL');
            $table->boolean('principal')->default(false);
            $table->timestamps();

            $table->unique(['tipo', 'numero', 'pais']);
            $table->index('persona_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_persona');
    }
};
