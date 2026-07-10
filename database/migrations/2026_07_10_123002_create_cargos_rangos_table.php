<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración.
     *
     * Catálogo compartido de cargos/rangos: NO lleva academia_id.
     */
    public function up(): void
    {
        Schema::create('cargos_rangos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->smallInteger('nivel'); // 1 = más alto
            $table->smallInteger('grado_dan')->nullable();
            $table->string('uniforme_gala')->nullable();
            $table->string('collar')->nullable();
            $table->string('grupo')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('cargos_rangos');
    }
};
