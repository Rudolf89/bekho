<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración.
     *
     * Pago registrado manualmente. Para mensualidad, `periodo` es el primer día
     * del mes cubierto; para matrícula queda null. El estado de morosidad NO se
     * guarda: se deriva de la ausencia de mensualidad del período vigente.
     */
    public function up(): void
    {
        // matricula_id (y su índice único) se añade en una migración posterior,
        // porque la tabla matriculas se crea después (rediseño Fase 2/4).
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_id')->constrained('grupos')->cascadeOnDelete();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipo'); // App\Enums\TipoPago
            $table->date('periodo')->nullable(); // primer día del mes (mensualidad)
            $table->unsignedInteger('monto'); // CLP
            $table->date('fecha_pago');
            $table->string('medio')->nullable();
            $table->timestamps();

            $table->index('grupo_id');
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
