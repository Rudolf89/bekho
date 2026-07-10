<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración.
     */
    public function up(): void
    {
        Schema::create('sedes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academia_id')->constrained('academias')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('direccion')->nullable();
            $table->string('comuna')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('academia_id');
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('sedes');
    }
};
