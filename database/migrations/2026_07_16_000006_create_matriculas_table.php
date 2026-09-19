<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Matrícula: vínculo alumno ↔ grupo (reemplaza a estudiantes en fases
 * posteriores). Dato operativo del grupo. La FK compuesta (sede_id, grupo_id)
 * garantiza que la sede sea del mismo grupo. Una persona solo puede tener UNA
 * matrícula activa a la vez (índice único parcial). matricula_origen_id enlaza
 * un traslado con su matrícula de origen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matriculas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id')->constrained('personas')->cascadeOnDelete();
            // Puente TRANSITORIO hacia estudiantes mientras la operación (exámenes,
            // etc.) aún usa Estudiante. Se elimina al retirar la tabla estudiantes.
            $table->foreignId('estudiante_id')->nullable()->constrained('estudiantes')->nullOnDelete();
            $table->foreignId('grupo_id')->constrained('grupos')->cascadeOnDelete();
            $table->foreignId('sede_id')->nullable();
            $table->string('grupo_etario'); // App\Enums\GrupoEtario (roster de la clase)
            $table->string('nivel')->nullable(); // App\Enums\NivelEntrenamiento (derivado del grado)
            $table->string('estado')->default('activa'); // App\Enums\EstadoMatricula
            $table->date('fecha_ingreso')->nullable();
            $table->date('fecha_retiro')->nullable();
            $table->string('motivo_baja')->nullable();
            $table->foreignId('instructor_persona_id')->nullable()->constrained('personas')->nullOnDelete();
            $table->unsignedTinyInteger('dia_vencimiento')->nullable();
            $table->timestamp('acepto_reglamento_at')->nullable();
            $table->foreignId('aceptado_por_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('matricula_origen_id')->nullable()->constrained('matriculas')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('grupo_id');
            $table->index('persona_id');
            $table->foreign(['sede_id', 'grupo_id'])
                ->references(['id', 'grupo_id'])->on('sedes')->cascadeOnDelete();
        });

        // Una sola matrícula activa por persona en toda la federación (índice
        // parcial: PostgreSQL y SQLite lo soportan con la misma sintaxis).
        DB::statement(
            'CREATE UNIQUE INDEX matriculas_una_activa ON matriculas (persona_id) '
            ."WHERE estado = 'activa' AND deleted_at IS NULL"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('matriculas');
    }
};
