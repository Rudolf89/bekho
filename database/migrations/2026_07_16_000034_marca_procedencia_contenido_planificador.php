<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Procedencia del contenido del planificador. El contenido sembrado desde
 * docs/planificador-unificado.tsx fue GENERADO CON IA, no transcrito de los
 * manuales ATA, y hasta ahora nada lo advertía. Se marca con:
 *   - fuente (string, nullable): de dónde viene el contenido.
 *   - verificado (bool, default false): si la federación ya lo validó.
 * Aplica a planificaciones de clase, planificador de Cinturón Negro y biblioteca
 * de calentamiento.
 */
return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $tablas = [
        'planificaciones_clase',
        'planificaciones_cinturon_negro',
        'categorias_calentamiento',
    ];

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
