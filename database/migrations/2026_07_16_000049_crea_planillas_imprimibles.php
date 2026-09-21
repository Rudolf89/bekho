<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Planillas imprimibles del programa: los formularios en papel que la escuela
 * usa en la cancha (planilla de examen de grado, checklist técnico, planillas de
 * arbitraje, registro de lección de vida…).
 *
 * Catálogo de la federación (sin grupo_id): la planilla es la misma para todos
 * los grupos. Cada una declara sus COLUMNAS, y con ellas el sistema imprime la
 * grilla en blanco para llenar a mano; `filas` dice cuántas líneas vacías salen.
 *
 * OJO con el nombre: `planillas_competencia` (jueces, competidores, puntajes) es
 * otra cosa y sigue existiendo aparte. Esta tabla es solo el papel imprimible.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planillas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('federacion_id')->constrained('federaciones')->restrictOnDelete();
            $table->string('nombre');
            // Cuándo se usa: "Examen del ciclo", "Torneo", "Certificación"…
            $table->string('uso')->nullable();
            $table->string('descripcion')->nullable();
            $table->string('estado')->default('borrador'); // App\Enums\EstadoPlanilla
            $table->unsignedInteger('version')->default(1);
            // Líneas en blanco que salen impresas bajo el encabezado de columnas.
            $table->unsignedInteger('filas')->default(20);
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('orden')->default(0);
            $table->string('fuente')->nullable();
            $table->boolean('verificado')->default(false);
            $table->timestamps();

            $table->unique(['federacion_id', 'nombre']);
        });

        Schema::create('columnas_planilla', function (Blueprint $table) {
            $table->id();
            // Composición: la columna no existe sin su planilla.
            $table->foreignId('planilla_id')->constrained('planillas')->cascadeOnDelete();
            $table->string('titulo');
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();

            $table->index(['planilla_id', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('columnas_planilla');
        Schema::dropIfExists('planillas');
    }
};
