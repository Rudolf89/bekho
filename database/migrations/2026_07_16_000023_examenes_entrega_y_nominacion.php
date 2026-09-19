<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Exámenes (Fase 6): entrega del cinturón y nominación.
 *
 * - graduaciones gana fecha_entrega (la entrega en ceremonia; el plazo de 30 días
 *   desde la aprobación solo alerta) y examinador_persona_id (quién examinó, por
 *   persona), distinto del instructor acreditado (instructor_id → users) que
 *   sostiene el conteo en cascada.
 * - nominaciones_examen: los grados con requiere_nominacion (rojo-negro y danes)
 *   exigen una nominación aprobada antes de graduar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('graduaciones', function (Blueprint $table) {
            $table->date('fecha_entrega')->nullable()->after('fecha');
            $table->foreignId('examinador_persona_id')->nullable()->after('instructor_id')
                ->constrained('personas')->nullOnDelete();
        });

        Schema::create('nominaciones_examen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id')->constrained('personas')->cascadeOnDelete();
            $table->foreignId('grado_objetivo_id')->constrained('grados')->restrictOnDelete();
            $table->foreignId('nominado_por_persona_id')->nullable()->constrained('personas')->nullOnDelete();
            $table->date('fecha')->nullable();
            $table->string('estado')->default('pendiente'); // App\Enums\EstadoNominacion
            $table->timestamps();

            $table->index(['persona_id', 'grado_objetivo_id']);
        });

        // Una sola nominación pendiente por persona y grado objetivo.
        DB::statement(
            'CREATE UNIQUE INDEX nominaciones_una_pendiente ON nominaciones_examen (persona_id, grado_objetivo_id) '
            ."WHERE estado = 'pendiente'"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('nominaciones_examen');

        Schema::table('graduaciones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('examinador_persona_id');
            $table->dropColumn('fecha_entrega');
        });
    }
};
