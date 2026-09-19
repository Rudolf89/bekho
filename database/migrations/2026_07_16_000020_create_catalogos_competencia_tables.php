<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rediseño Fase 6: catálogos de competencia de la federación (para las pruebas
 * de certificación de planillero). Grupos de edad, categorías, pruebas con sus
 * criterios de evaluación (por papel de juez y escala) y la tabla de libres.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grupos_edad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('federacion_id')->constrained('federaciones')->cascadeOnDelete();
            $table->string('nombre');
            $table->unsignedSmallInteger('edad_desde')->nullable();
            $table->unsignedSmallInteger('edad_hasta')->nullable();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('categorias_competencia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('federacion_id')->constrained('federaciones')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('tipo'); // color | negro
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('pruebas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('federacion_id')->constrained('federaciones')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('modalidad'); // formas | armas | combate
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('criterios_prueba', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prueba_id')->constrained('pruebas')->cascadeOnDelete();
            $table->string('papel_juez'); // App\Enums\PapelJuez (a | central | b)
            $table->string('nombre');
            $table->foreignId('escala_id')->nullable()->constrained('escalas_puntaje')->nullOnDelete();
            $table->boolean('permite_cero')->default(false);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('tabla_libres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('federacion_id')->constrained('federaciones')->cascadeOnDelete();
            $table->unsignedSmallInteger('competidores');
            $table->unsignedSmallInteger('libres');
            $table->timestamps();

            $table->unique(['federacion_id', 'competidores']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tabla_libres');
        Schema::dropIfExists('criterios_prueba');
        Schema::dropIfExists('pruebas');
        Schema::dropIfExists('categorias_competencia');
        Schema::dropIfExists('grupos_edad');
    }
};
