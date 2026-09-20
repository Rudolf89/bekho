<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Créditos de graduación (avance del distintivo de collar del profesor).
 *
 * Cada graduación suma un punto al instructor de origen y a toda su cadena de
 * supervisión hacia arriba (ver App\Services\ServicioCreditos). El distintivo
 * NO se guarda: se calcula contando créditos contra los umbrales de su rango.
 *
 * - sedes.responsable_persona_id: responsable de la sede (regla de origen 2).
 * - creditos_graduacion: una fila por (graduación, persona) de la cadena.
 * - distintivos_rango: umbrales de graduados por distintivo de cada rango
 *   (umbrales POR CONFIRMAR por la federación → nullable, no se inventan).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sedes', function (Blueprint $table) {
            $table->foreignId('responsable_persona_id')->nullable()->after('grupo_id')
                ->constrained('personas')->nullOnDelete();
        });

        Schema::create('creditos_graduacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('graduacion_id')->constrained('graduaciones')->restrictOnDelete();
            $table->foreignId('persona_id')->constrained('personas')->restrictOnDelete();
            // 1 = origen (instructor acreditado); 2, 3, … = cadena de supervisión.
            $table->unsignedTinyInteger('posicion');
            $table->timestamps();

            $table->unique(['graduacion_id', 'persona_id']);
            $table->index('persona_id');
        });

        Schema::create('distintivos_rango', function (Blueprint $table) {
            $table->id();
            $table->foreignId('federacion_id')->constrained('federaciones')->restrictOnDelete();
            $table->foreignId('rango_id')->constrained('cargos_rangos')->restrictOnDelete();
            $table->string('nombre');
            // Graduados acumulados (créditos) para alcanzar el distintivo. POR
            // CONFIRMAR: nullable hasta que la federación fije los umbrales.
            $table->unsignedInteger('graduados_requeridos')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();

            $table->unique(['rango_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distintivos_rango');
        Schema::dropIfExists('creditos_graduacion');

        Schema::table('sedes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('responsable_persona_id');
        });
    }
};
