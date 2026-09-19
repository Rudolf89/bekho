<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rediseño Fase 3: los tramos de entrenamiento pasan de ser un enum
 * (App\Enums\NivelEntrenamiento) a un CATÁLOGO de la federación, para poder
 * adaptarse a otra federación o arte marcial. Para BEKHO: principiantes,
 * intermedio, avanzado, rojo-negro, danes. Aditivo: el enum sigue mandando en
 * la operación hasta el recableo de fases posteriores.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tramos_entrenamiento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('federacion_id')->constrained('federaciones')->cascadeOnDelete();
            $table->string('clave'); // slug estable (coincide con NivelEntrenamiento)
            $table->string('nombre');
            $table->unsignedSmallInteger('orden')->default(0);
            $table->string('color')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['federacion_id', 'clave']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tramos_entrenamiento');
    }
};
