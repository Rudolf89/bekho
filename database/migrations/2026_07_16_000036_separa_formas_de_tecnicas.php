<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Separa las FORMAS de las técnicas. Una forma es una secuencia con nombre
 * coreano, significado y grado; una técnica es un movimiento. Antes ambas vivían
 * en tecnicas/pasos_tecnica y dejaban columnas vacías en cada caso.
 *
 * - formas: catálogo de la federación (sin grupo_id) con procedencia.
 * - pasos_forma: los movimientos de la forma; posicion_id → posiciones (leyenda);
 *   la sección y los modificadores/combinadores quedan como texto en el paso.
 * - Las formas que hoy viven en tecnicas se retiran (el seeder las recrea en
 *   formas). En base nueva es un no-op (tecnicas se siembra después).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('formas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('federacion_id')->constrained('federaciones')->restrictOnDelete();
            $table->string('nombre');
            $table->string('nombre_coreano')->nullable();
            $table->string('significado')->nullable();
            $table->foreignId('grado_id')->nullable()->constrained('grados')->restrictOnDelete();
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->string('fuente')->nullable();
            $table->boolean('verificado')->default(false);
            $table->timestamps();

            $table->unique(['federacion_id', 'nombre']);
        });

        Schema::create('pasos_forma', function (Blueprint $table) {
            $table->id();
            $table->foreignId('forma_id')->constrained('formas')->restrictOnDelete();
            $table->unsignedInteger('numero');
            $table->string('lado')->nullable();
            $table->text('tecnica'); // el movimiento, tal como lo trae el manual
            $table->foreignId('posicion_id')->nullable()->constrained('posiciones')->nullOnDelete();
            $table->string('seccion')->nullable();       // altura como texto (admite & y /)
            $table->string('modificadores')->nullable(); // KIHAP, tensión, lento, etc. (texto)
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();

            $table->unique(['forma_id', 'numero']);
        });

        // Retira las formas que vivían en tecnicas (sus pasos caen por cascade).
        // En migrate:fresh es un no-op: tecnicas aún no tiene filas.
        DB::table('tecnicas')->where('categoria', 'forma')->delete();
    }

    public function down(): void
    {
        Schema::dropIfExists('pasos_forma');
        Schema::dropIfExists('formas');
    }
};
