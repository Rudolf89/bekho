<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rediseño Fase 5b: el pago deja de ser "una mensualidad por matrícula y período"
 * y pasa a ser un abono con verificación que se aplica a uno o varios cargos vía
 * el pivote pago_cargo. Un pago nace "por verificar" (comprobante de transferencia)
 * y recepción/dirección lo confirma; al verificarse, los cargos que cubre quedan
 * pagados. La morosidad se lee de los cargos pendientes, no de esta tabla.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            // El pago ya no cuelga de una sola matrícula/tipo/período: se aplica a
            // cargos concretos. Se identifica a quién pagó (apoderado o alumno).
            $table->dropUnique(['matricula_id', 'tipo', 'periodo']);
            $table->dropConstrainedForeignId('matricula_id');
            $table->dropColumn(['tipo', 'periodo', 'medio']);

            $table->foreignId('pagado_por_persona_id')->nullable()->after('grupo_id')
                ->constrained('personas')->nullOnDelete();
            $table->string('banco')->nullable()->after('fecha_pago');
            $table->string('referencia')->nullable()->after('banco');
            $table->string('comprobante_archivo')->nullable()->after('referencia');
            $table->string('estado')->default('por_verificar')->after('comprobante_archivo'); // App\Enums\EstadoPago
            $table->foreignId('verificado_por_user_id')->nullable()->after('estado')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('verificado_at')->nullable()->after('verificado_por_user_id');
            $table->string('motivo_anulacion')->nullable()->after('verificado_at');
        });

        // Pivote pago ↔ cargo: permite abonos y pagos que cubren varios cargos.
        Schema::create('pago_cargo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pago_id')->constrained('pagos')->cascadeOnDelete();
            $table->foreignId('cargo_id')->constrained('cargos')->cascadeOnDelete();
            $table->unsignedInteger('monto_aplicado');
            $table->timestamps();

            $table->unique(['pago_id', 'cargo_id']);
            $table->index('cargo_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pago_cargo');

        Schema::table('pagos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pagado_por_persona_id');
            $table->dropConstrainedForeignId('verificado_por_user_id');
            $table->dropColumn(['banco', 'referencia', 'comprobante_archivo', 'estado', 'verificado_at', 'motivo_anulacion']);

            $table->foreignId('matricula_id')->nullable()->after('grupo_id')->constrained('matriculas')->cascadeOnDelete();
            $table->string('tipo')->after('matricula_id');
            $table->date('periodo')->nullable()->after('tipo');
            $table->string('medio')->nullable()->after('fecha_pago');
            $table->unique(['matricula_id', 'tipo', 'periodo']);
        });
    }
};
