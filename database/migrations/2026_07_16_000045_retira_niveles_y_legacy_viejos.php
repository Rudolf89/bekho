<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Unificación LMS + Legacy — commit (d): retira el modelo viejo (contract).
 *
 * Ya todo vive en programas → etapas_programa → contenidos/requisitos_etapa y en
 * inscripciones_programa/horas_programa/… (commits a–c). Aquí se elimina el
 * modelo paralelo:
 *  - contenidos deja de colgar de un nivel y de un grupo: pasa a ser catálogo de
 *    la federación bajo su etapa (etapa_programa_id NOT NULL).
 *  - progreso_contenidos cuelga de la persona (se quitan user_id y grupo_id; el
 *    que registró queda en registrado_por_user_id).
 *  - se eliminan las tablas niveles, niveles_legacy, requisitos_legacy,
 *    inscripciones_legacy, horas_legacy y cumplimiento_requisitos.
 *
 * No es reversible en datos (elimina el modelo viejo); el down recrea la
 * estructura mínima para poder revertir el esquema.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Contenido: catálogo de la federación bajo su etapa (etapa_programa_id ya
        // existe desde el commit a; la app garantiza que siempre se fije).
        Schema::table('contenidos', function (Blueprint $table) {
            // Los índices referencian las columnas: hay que soltarlos antes.
            $table->dropIndex(['nivel_id', 'orden']);
            $table->dropIndex(['grupo_id']);
        });
        Schema::table('contenidos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('nivel_id');
            $table->dropConstrainedForeignId('grupo_id');
        });

        // Progreso por persona (el user queda solo como "quién registró").
        Schema::table('progreso_contenidos', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'contenido_id']);
            $table->dropIndex(['grupo_id']);
        });
        Schema::table('progreso_contenidos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropConstrainedForeignId('grupo_id');
            $table->unique(['persona_id', 'contenido_id']);
        });

        // Fuera el modelo viejo.
        Schema::dropIfExists('cumplimiento_requisitos');
        Schema::dropIfExists('horas_legacy');
        Schema::dropIfExists('inscripciones_legacy');
        Schema::dropIfExists('requisitos_legacy');
        Schema::dropIfExists('niveles_legacy');
        Schema::dropIfExists('niveles');
    }

    public function down(): void
    {
        // Recrea niveles y el enlace por nivel/grupo (estructura mínima).
        Schema::create('niveles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_id')->constrained('grupos')->cascadeOnDelete();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->smallInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::table('contenidos', function (Blueprint $table) {
            $table->foreignId('grupo_id')->nullable()->constrained('grupos')->cascadeOnDelete();
            $table->foreignId('nivel_id')->nullable()->constrained('niveles')->cascadeOnDelete();
        });

        Schema::table('progreso_contenidos', function (Blueprint $table) {
            $table->dropUnique(['persona_id', 'contenido_id']);
            $table->foreignId('grupo_id')->nullable()->constrained('grupos')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->unique(['user_id', 'contenido_id']);
        });

        // Las tablas Legacy viejas no se recrean con detalle (modelo retirado).
    }
};
