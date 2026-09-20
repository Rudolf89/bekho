<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Procedencia (fuente/verificado) para el contenido del Manual ATA Legacy que ya
 * tenía tabla propia: niveles y requisitos del Programa Legacy y los Cuadrantes
 * de Enseñanza. Así estos catálogos quedan marcados como contenido verificado del
 * manual, igual que el resto del lote.
 */
return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $tablas = ['niveles_legacy', 'requisitos_legacy', 'cuadrante_items'];

    public function up(): void
    {
        foreach ($this->tablas as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->string('fuente')->nullable();
                $table->boolean('verificado')->default(false);
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tablas as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropColumn(['fuente', 'verificado']);
            });
        }
    }
};
