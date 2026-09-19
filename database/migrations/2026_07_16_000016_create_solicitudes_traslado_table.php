<?php

use App\Enums\EstadoTraslado;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rediseño Fase 4: solicitud de traslado de un alumno entre grupos. La inicia el
 * grupo destino; consiente el titular adulto o un apoderado; el grupo de origen
 * aprueba (o queda bloqueada por deuda). El plazo se guarda como plazo_desde +
 * dias_consumidos (se pausa mientras está bloqueada). Una sola solicitud
 * pendiente por persona (índice parcial). NO lleva grupo_id: cruza dos grupos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes_traslado', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id')->constrained('personas')->cascadeOnDelete();
            $table->foreignId('matricula_origen_id')->nullable()->constrained('matriculas')->nullOnDelete();
            $table->foreignId('grupo_destino_id')->constrained('grupos')->cascadeOnDelete();
            $table->foreignId('sede_destino_id')->nullable()->constrained('sedes')->nullOnDelete();
            $table->string('estado')->default(EstadoTraslado::PendienteConsentimiento->value);
            $table->foreignId('solicitada_por_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('consentido_por_persona_id')->nullable()->constrained('personas')->nullOnDelete();
            $table->timestamp('consentimiento_at')->nullable();
            $table->string('consentimiento_medio')->nullable();
            $table->string('consentimiento_respaldo')->nullable();
            $table->foreignId('resuelta_por_persona_id')->nullable()->constrained('personas')->nullOnDelete();
            $table->string('motivo')->nullable();
            $table->date('plazo_desde')->nullable();
            $table->unsignedSmallInteger('dias_consumidos')->default(0);
            $table->timestamps();

            $table->index('persona_id');
            $table->index('grupo_destino_id');
        });

        // Una sola solicitud pendiente por persona (índice parcial: PostgreSQL y
        // SQLite comparten la sintaxis).
        $pendientes = "'".implode("', '", EstadoTraslado::pendientes())."'";
        DB::statement(
            'CREATE UNIQUE INDEX traslados_uno_pendiente ON solicitudes_traslado (persona_id) '
            ."WHERE estado IN ({$pendientes})"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_traslado');
    }
};
