<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enriquece el catálogo de grados: distinción recomendado/decidido/dan (tipo) y
 * el número de franjas/barras del cinturón (los danes llevan una por grado).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grados', function (Blueprint $table) {
            $table->string('tipo')->default('base')->after('color'); // App\Enums\TipoGrado
            $table->unsignedTinyInteger('franjas')->default(0)->after('tipo');
        });
    }

    public function down(): void
    {
        Schema::table('grados', function (Blueprint $table) {
            $table->dropColumn(['tipo', 'franjas']);
        });
    }
};
