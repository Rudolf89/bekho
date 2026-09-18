<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración.
     *
     * Catálogo compartido de grados (cinturones): NO lleva grupo_id. Soporta
     * múltiples escalas: 'tigers', 'for_kids' y 'adultos'. Ver App\Enums\EscalaGrado.
     */
    public function up(): void
    {
        Schema::create('grados', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->smallInteger('orden')->default(0);
            $table->string('escala'); // App\Enums\EscalaGrado
            $table->string('color')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['escala', 'orden']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('grados');
    }
};
