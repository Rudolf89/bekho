<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Follow-up de horas del Programa Legacy: registro de asistencia del trainee con
 * papel de AYUDANTE, por sesión. Cada marca (persona × clase × fecha) congela las
 * horas = suma de la duración de los horarios de esa clase ese día, y acredita
 * esas horas a la inscripción activa cuya etapa exige horas (ver
 * App\Services\ServicioHorasAyudante). Operativo (con grupo_id).
 *
 * horas_programa gana `asistencia_ayudante_id` para poder deshacer la marca (y su
 * hora) sin recalcular las demás: las horas quedan congeladas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asistencias_ayudante', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_id')->constrained('grupos')->cascadeOnDelete();
            // La persona es historial de formación → restrictOnDelete.
            $table->foreignId('persona_id')->constrained('personas')->restrictOnDelete();
            $table->foreignId('clase_id')->constrained('clases')->cascadeOnDelete();
            $table->date('fecha');
            $table->decimal('horas', 6, 2)->default(0);
            $table->foreignId('registrado_por_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['persona_id', 'clase_id', 'fecha'], 'ayudante_sesion_unica');
            $table->index('grupo_id');
        });

        Schema::table('horas_programa', function (Blueprint $table) {
            // Origen de la hora cuando proviene de una asistencia de ayudante; al
            // borrar la marca se anula el vínculo (la hora ya está congelada).
            $table->foreignId('asistencia_ayudante_id')->nullable()->after('origen')
                ->constrained('asistencias_ayudante')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('horas_programa', function (Blueprint $table) {
            $table->dropConstrainedForeignId('asistencia_ayudante_id');
        });

        Schema::dropIfExists('asistencias_ayudante');
    }
};
