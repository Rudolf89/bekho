<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 0 del rediseño del modelo de datos: la FEDERACIÓN es la entidad raíz.
 * BEKHO es una federación; cada grupo (ex «academia») pertenece a una. Los
 * catálogos (grados, rangos, programas, etc.) se acotarán a la federación en
 * las fases siguientes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('federaciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('razon_social')->nullable();
            $table->string('pais')->nullable();
            $table->string('moneda', 3)->nullable();
            $table->string('logo')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('federaciones');
    }
};
