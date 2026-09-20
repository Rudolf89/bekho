<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Procedencia de los requisitos de etapa (`fuente`/`verificado`), como el resto
 * del contenido del Manual ATA Legacy. Necesario para reponer los requisitos
 * Protech por nivel (verificados, del manual) que se perdieron al reescribir los
 * seeders en la unificación.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requisitos_etapa', function (Blueprint $table) {
            $table->string('fuente')->nullable()->after('orden');
            $table->boolean('verificado')->default(false)->after('fuente');
        });
    }

    public function down(): void
    {
        Schema::table('requisitos_etapa', function (Blueprint $table) {
            $table->dropColumn(['fuente', 'verificado']);
        });
    }
};
