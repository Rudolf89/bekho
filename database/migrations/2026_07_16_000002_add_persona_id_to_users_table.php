<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rediseño Fase 2: el usuario pasa a ser SOLO acceso, colgado de una persona.
 * Se añade persona_id (único). La reducción del resto de columnas (nombre,
 * rango, supervisión → a persona/instructor) se hará al recablear la operación
 * en fases posteriores; por ahora el enlace es aditivo y nullable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('persona_id')->nullable()->unique()->after('id')
                ->constrained('personas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('persona_id');
        });
    }
};
