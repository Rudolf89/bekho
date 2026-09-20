<?php

use App\Support\Formacion\MigraFormacionAPersona;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Unificación LMS + Legacy — commit (b): lo operativo pasa a la PERSONA.
 *
 * Los menores no tienen cuenta de usuario (acceden por su apoderado), así que la
 * formación no puede colgar de user_id. Se crean las tablas operativas por
 * persona (inscripción en un programa, horas, cumplimiento de requisitos y
 * ascensos) y `progreso_contenidos` gana persona_id.
 *
 * La inscripción a un programa es TRANSVERSAL (sin grupo_id, como instructores o
 * creditos_graduacion): la formación sigue a la persona aunque cambie de grupo.
 * Sus detalles (horas/cumplimientos/ascensos) son composición de la inscripción
 * (cascadeOnDelete); la inscripción misma es historial de la persona y se
 * protege con restrictOnDelete sobre persona_id.
 *
 * Migración ADITIVA (expand): no se tocan inscripciones_legacy/horas_legacy ni
 * user_id en progreso_contenidos; el modelo viejo sigue vivo hasta el commit (d).
 * El traslado de datos existentes lo hace App\Support\Formacion\MigraFormacionAPersona.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscripciones_programa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id')->constrained('personas')->restrictOnDelete();
            $table->foreignId('programa_id')->constrained('programas')->restrictOnDelete();
            // Etapa en curso de la ruta (nulo = aún sin etapa asignada).
            $table->foreignId('etapa_actual_id')->nullable()->constrained('etapas_programa')->nullOnDelete();
            $table->string('estado')->default('en_curso'); // App\Enums\EstadoLegacy
            $table->date('fecha_ingreso')->nullable();
            $table->date('fecha_aprobacion')->nullable();
            // El ascenso lo aprueba el instructor del alumno (persona), no un user.
            $table->foreignId('aprobado_por_persona_id')->nullable()->constrained('personas')->nullOnDelete();
            $table->text('nota')->nullable();
            $table->timestamps();

            $table->unique(['persona_id', 'programa_id']);
        });

        Schema::create('horas_programa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscripcion_programa_id')->constrained('inscripciones_programa')->cascadeOnDelete();
            $table->date('fecha');
            $table->decimal('horas', 6, 2)->default(0);
            // Origen del registro: por ahora 'manual'; el cálculo desde la
            // asistencia con papel de ayudante queda como follow-up.
            $table->string('origen')->default('manual');
            $table->string('descripcion')->nullable();
            $table->foreignId('registrado_por_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('inscripcion_programa_id');
        });

        Schema::create('cumplimientos_requisito', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscripcion_programa_id')->constrained('inscripciones_programa')->cascadeOnDelete();
            $table->foreignId('requisito_etapa_id')->constrained('requisitos_etapa')->cascadeOnDelete();
            $table->timestamp('cumplido_at')->nullable();
            $table->foreignId('aprobado_por_persona_id')->nullable()->constrained('personas')->nullOnDelete();
            $table->timestamps();

            $table->unique(['inscripcion_programa_id', 'requisito_etapa_id'], 'cumplimiento_etapa_unico');
        });

        Schema::create('ascensos_programa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscripcion_programa_id')->constrained('inscripciones_programa')->cascadeOnDelete();
            $table->foreignId('etapa_programa_id')->constrained('etapas_programa')->restrictOnDelete();
            $table->date('fecha');
            $table->foreignId('aprobado_por_persona_id')->nullable()->constrained('personas')->nullOnDelete();
            $table->timestamps();

            $table->index('inscripcion_programa_id');
        });

        // El progreso de contenidos pasa a colgar de la persona (el user que lo
        // registró queda como dato aparte). Se mantiene user_id durante el expand.
        Schema::table('progreso_contenidos', function (Blueprint $table) {
            $table->foreignId('persona_id')->nullable()->after('user_id')
                ->constrained('personas')->restrictOnDelete();
            $table->foreignId('registrado_por_user_id')->nullable()->after('persona_id')
                ->constrained('users')->nullOnDelete();
            $table->index('persona_id');
        });

        // Traslada los datos existentes al nuevo modelo por persona. En
        // migrate:fresh corre sobre tablas vacías (los seeders van después): es
        // un no-op seguro; en un despliegue real con datos, los migra sin pérdida.
        (new MigraFormacionAPersona)->ejecutar();
    }

    public function down(): void
    {
        Schema::table('progreso_contenidos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('registrado_por_user_id');
            $table->dropConstrainedForeignId('persona_id');
        });

        Schema::dropIfExists('ascensos_programa');
        Schema::dropIfExists('cumplimientos_requisito');
        Schema::dropIfExists('horas_programa');
        Schema::dropIfExists('inscripciones_programa');
    }
};
