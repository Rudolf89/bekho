<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración.
     *
     * Planilla: rutina de una clase, definida por grupo etario × nivel × programa.
     * El contenido diferenciado por edad se logra con una planilla por grupo.
     * programa_id es opcional (null = clase regular, sin disciplina asociada).
     *
     * Es TRANSVERSAL: el currículo/rutina es contenido ATA/BEKHO compartido por
     * toda la federación (sin grupo_id), como cargos_rangos.
     */
    public function up(): void
    {
        Schema::create('planillas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programa_id')->nullable()->constrained('programas')->nullOnDelete();
            $table->string('nombre');
            $table->string('grupo_etario'); // App\Enums\GrupoEtario
            $table->string('nivel'); // App\Enums\NivelEntrenamiento
            $table->string('habilidad_vida')->nullable(); // App\Enums\HabilidadVida (del día/semana)
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['grupo_etario', 'nivel']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('planillas');
    }
};
