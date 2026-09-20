<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fuente de una técnica para la comparativa de currículo: 'bekho' (lo que se
 * enseña y se rinde en examen en BEKHO Chile) vs 'ata' (la referencia del manual
 * oficial). Nula en técnicas sin comparación (formas, armas, etc.).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tecnicas', function (Blueprint $table) {
            $table->string('fuente')->nullable()->after('categoria');
        });
    }

    public function down(): void
    {
        Schema::table('tecnicas', function (Blueprint $table) {
            $table->dropColumn('fuente');
        });
    }
};
