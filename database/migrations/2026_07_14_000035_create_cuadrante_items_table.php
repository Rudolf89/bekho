<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Items de los Cuadrantes de Enseñanza (marco pedagógico ATA). Cada cuadrante
 * (Estructura, Emoción, Conocimiento, Legado) define responsabilidades del
 * alumno y del instructor, como lista de verificación. Catálogo compartido.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuadrante_items', function (Blueprint $table) {
            $table->id();
            $table->string('cuadrante'); // App\Enums\Cuadrante
            $table->string('rol');       // App\Enums\RolCuadrante
            $table->unsignedInteger('orden')->default(0);
            $table->string('texto');
            $table->text('detalle')->nullable();
            $table->timestamps();

            $table->index(['cuadrante', 'rol', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuadrante_items');
    }
};
