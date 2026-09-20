<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogos del Manual del Facilitador ATA Legacy v4 (contenido verificado):
 * leyenda de formas (posiciones y secciones de altura), habilidades para la vida,
 * atributos técnicos (rúbrica) y armas (Protech). Catálogos de la federación
 * (sin grupo_id). Llevan fuente/verificado (procedencia) como el resto del
 * contenido. Se siembran desde database/data/*.json (ManualLegacySeeder).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Leyenda de formas: posiciones (F, B, M, S, C, R, X, OL, P, HS, --).
        Schema::create('posiciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('federacion_id')->constrained('federaciones')->restrictOnDelete();
            $table->string('codigo');
            $table->string('nombre');
            $table->string('fuente')->nullable();
            $table->boolean('verificado')->default(false);
            $table->timestamps();

            $table->unique(['federacion_id', 'codigo']);
        });

        // Leyenda de formas: secciones de altura (H, M, L).
        Schema::create('secciones_altura', function (Blueprint $table) {
            $table->id();
            $table->foreignId('federacion_id')->constrained('federaciones')->restrictOnDelete();
            $table->string('codigo');
            $table->string('nombre');
            $table->string('fuente')->nullable();
            $table->boolean('verificado')->default(false);
            $table->timestamps();

            $table->unique(['federacion_id', 'codigo']);
        });

        // Habilidades para la vida (las 6 del manual).
        Schema::create('habilidades_vida', function (Blueprint $table) {
            $table->id();
            $table->foreignId('federacion_id')->constrained('federaciones')->restrictOnDelete();
            $table->string('nombre');
            $table->text('definicion')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->string('fuente')->nullable();
            $table->boolean('verificado')->default(false);
            $table->timestamps();

            $table->unique(['federacion_id', 'nombre']);
        });

        // Atributos técnicos (rúbrica de 10) + criterios de conocimiento de forma
        // (3), distinguidos por `tipo` (atributo | criterio_forma).
        Schema::create('atributos_tecnicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('federacion_id')->constrained('federaciones')->restrictOnDelete();
            $table->string('tipo')->default('atributo'); // App\Enums\TipoAtributoTecnico
            $table->string('nombre');
            $table->text('definicion')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->string('fuente')->nullable();
            $table->boolean('verificado')->default(false);
            $table->timestamps();

            $table->unique(['federacion_id', 'tipo', 'nombre']);
        });

        // Armas del programa Protech.
        Schema::create('armas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('federacion_id')->constrained('federaciones')->restrictOnDelete();
            $table->string('abreviatura');
            $table->string('nombre');
            $table->string('nombre_comun')->nullable();
            $table->string('modalidad')->nullable();
            $table->string('fuente')->nullable();
            $table->boolean('verificado')->default(false);
            $table->timestamps();

            $table->unique(['federacion_id', 'abreviatura']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('armas');
        Schema::dropIfExists('atributos_tecnicos');
        Schema::dropIfExists('habilidades_vida');
        Schema::dropIfExists('secciones_altura');
        Schema::dropIfExists('posiciones');
    }
};
