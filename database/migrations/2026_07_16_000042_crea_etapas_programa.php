<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Unificación LMS + Legacy — commit (a): etapas y catálogo.
 *
 * El LMS (niveles→contenidos) y el Programa Legacy (niveles_legacy→requisitos)
 * eran dos modelos paralelos para lo mismo: una ruta formativa con etapas. Se
 * unen bajo `programas` (catálogo de la federación) → `etapas_programa`.
 *
 * Esta migración es ADITIVA (patrón expand): crea las estructuras nuevas y las
 * columnas de enlace SIN quitar `niveles`, `niveles_legacy` ni `nivel_id`. La
 * fusión de datos la hace un seeder idempotente (catálogo reseeded); la retirada
 * de las tablas viejas y el cambio de UI van en el commit (d). Así cada commit
 * intermedio queda en verde.
 */
return new class extends Migration
{
    public function up(): void
    {
        // --- Enriquecer el catálogo de programas --------------------------------
        Schema::table('programas', function (Blueprint $table) {
            // Los programas son catálogo de la federación (sin grupo_id).
            $table->foreignId('federacion_id')->nullable()->after('id')
                ->constrained('federaciones')->restrictOnDelete();
            // Grado mínimo para INGRESAR al programa (además de edad_minima).
            $table->foreignId('grado_minimo_id')->nullable()->after('edad_minima')
                ->constrained('grados')->restrictOnDelete();
            $table->string('fuente')->nullable()->after('orden');
            $table->boolean('verificado')->default(false)->after('fuente');
        });

        // Respaldo para datos existentes (en migrate:fresh la tabla está vacía a
        // esta altura; el seeder fija federacion_id al sembrar).
        if ($federacionId = DB::table('federaciones')->min('id')) {
            DB::table('programas')->whereNull('federacion_id')->update(['federacion_id' => $federacionId]);
        }

        // --- Etapas de un programa (fusión de niveles LMS + niveles_legacy) -----
        Schema::create('etapas_programa', function (Blueprint $table) {
            $table->id();
            // Composición: la etapa no existe sin su programa (catálogo).
            $table->foreignId('programa_id')->constrained('programas')->cascadeOnDelete();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->smallInteger('orden')->default(0);
            // Horas exigidas para completar la etapa (Legacy: 100; LMS: null).
            $table->unsignedInteger('horas_requeridas')->nullable();
            // Requisitos de AVANCE a esta etapa (Legacy: 13/16/18 y 1.er Dan en la 3).
            $table->smallInteger('edad_minima')->nullable();
            $table->foreignId('grado_minimo_id')->nullable()->constrained('grados')->restrictOnDelete();
            $table->boolean('activo')->default(true);
            $table->string('fuente')->nullable();
            $table->boolean('verificado')->default(false);
            $table->timestamps();

            $table->index(['programa_id', 'orden']);
        });

        // --- Requisitos de una etapa (fusión de requisitos_legacy) --------------
        Schema::create('requisitos_etapa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etapa_programa_id')->constrained('etapas_programa')->cascadeOnDelete();
            $table->string('descripcion');
            $table->string('tipo')->nullable(); // App\Enums\TipoRequisitoEtapa
            $table->unsignedInteger('cantidad')->nullable();
            // Un requisito puede exigir aprobar un cuestionario (prueba escrita).
            $table->foreignId('cuestionario_id')->nullable()->constrained('cuestionarios')->nullOnDelete();
            $table->smallInteger('orden')->default(0);
            $table->timestamps();

            $table->index(['etapa_programa_id', 'orden']);
        });

        // --- Enlaces desde el contenido y los cuestionarios ---------------------
        Schema::table('contenidos', function (Blueprint $table) {
            // Composición: el contenido pertenece a una etapa (nullable durante el
            // expand; nivel_id se mantiene hasta el commit d).
            $table->foreignId('etapa_programa_id')->nullable()->after('nivel_id')
                ->constrained('etapas_programa')->cascadeOnDelete();
        });

        Schema::table('cuestionarios', function (Blueprint $table) {
            // Cuestionario de una etapa; nulo = cuestionario suelto. Si se borra la
            // etapa, el cuestionario sobrevive (queda suelto).
            $table->foreignId('etapa_programa_id')->nullable()->after('id')
                ->constrained('etapas_programa')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cuestionarios', function (Blueprint $table) {
            $table->dropConstrainedForeignId('etapa_programa_id');
        });

        Schema::table('contenidos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('etapa_programa_id');
        });

        Schema::dropIfExists('requisitos_etapa');
        Schema::dropIfExists('etapas_programa');

        Schema::table('programas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('grado_minimo_id');
            $table->dropConstrainedForeignId('federacion_id');
            $table->dropColumn(['fuente', 'verificado']);
        });
    }
};
