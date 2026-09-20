<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rediseño Fase 5: cargo (cobro) generado a una matrícula por un tipo de cargo y
 * período. Se congela el detalle_calculo (tramo, cantidad de alumnos, beca) para
 * que un cargo pasado no cambie si luego cambian las tarifas. Único por
 * matrícula, tipo y período mientras no esté anulado (índice parcial).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cargos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_id')->constrained('grupos')->cascadeOnDelete();
            $table->foreignId('matricula_id')->constrained('matriculas')->cascadeOnDelete();
            $table->foreignId('tipo_cargo_id')->constrained('tipos_cargo')->cascadeOnDelete();
            $table->date('periodo')->nullable(); // primer día del mes (recurrentes)
            $table->unsignedInteger('monto');
            $table->date('vence_el')->nullable();
            $table->string('estado')->default('pendiente'); // App\Enums\EstadoCargo
            $table->json('detalle_calculo')->nullable();
            $table->timestamps();

            $table->index('grupo_id');
            $table->index('matricula_id');
        });

        // Único por matrícula + tipo + período mientras no esté anulado.
        DB::statement(
            'CREATE UNIQUE INDEX cargos_unico_periodo ON cargos (matricula_id, tipo_cargo_id, periodo) '
            ."WHERE estado <> 'anulado'"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('cargos');
    }
};
