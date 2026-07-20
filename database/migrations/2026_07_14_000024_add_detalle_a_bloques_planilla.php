<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enriquece los bloques de planilla con el detalle del planificador real:
 * - `tiempo`: tramo horario del bloque ("0–7 min").
 * - `titulo`: nombre visible del bloque (p. ej. "Fórmula: Songahm 3"), que puede
 *   variar respecto de la etiqueta fija del `tipo`, o cubrir bloques que no
 *   corresponden a ningún `TipoBloque` (Tigers y Cinturón Negro).
 * - `cuadrante_texto` / `cuadrante_color`: la etiqueta pedagógica del bloque
 *   (p. ej. "Memorización / Emoción"), más rica que el enum Cuadrante de 4
 *   valores (que sigue siendo la capa transversal de `cuadrantes_planilla`).
 *
 * `tipo` pasa a ser nullable: los bloques propios de Tigers (Juego de Golpes,
 * Mini Sparring, Cierre y Premio) y de Cinturón Negro no mapean a un TipoBloque.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bloques_planilla', function (Blueprint $table) {
            $table->string('tiempo')->nullable()->after('tipo');
            $table->string('titulo')->nullable()->after('tiempo');
            $table->string('cuadrante_texto')->nullable()->after('contenido');
            $table->string('cuadrante_color')->nullable()->after('cuadrante_texto');
        });

        // El tipo deja de ser obligatorio (bloques de Tigers/Cinturón Negro).
        Schema::table('bloques_planilla', function (Blueprint $table) {
            $table->string('tipo')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('bloques_planilla', function (Blueprint $table) {
            $table->dropColumn(['tiempo', 'titulo', 'cuadrante_texto', 'cuadrante_color']);
        });
    }
};
