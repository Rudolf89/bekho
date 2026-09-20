<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cambio de regla de negocio: el cobro (matrícula y mensualidad) es POR SEDE, no
 * por grupo. `tarifas_grupo` pasa a `tarifas_sede` (sede_id en vez de grupo_id) y
 * cada cargo guarda su `sede_id` (la sede de la matrícula, que es la que cobra).
 *
 * Migración de datos: cada tarifa del grupo se copia a TODAS sus sedes con los
 * mismos valores; cada sede los ajustará después. No hay tarifa de respaldo a
 * nivel de grupo. En una base recién creada (tarifas_grupo vacía) esto no copia
 * nada; solo deja el esquema final.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarifas_sede', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sede_id')->constrained('sedes')->restrictOnDelete();
            $table->foreignId('tipo_cargo_id')->constrained('tipos_cargo')->restrictOnDelete();
            $table->unsignedSmallInteger('cantidad_alumnos')->default(1); // tramo (familia en la sede)
            $table->unsignedInteger('monto_por_alumno');
            $table->date('vigente_desde')->nullable();
            $table->timestamps();

            $table->unique(['sede_id', 'tipo_cargo_id', 'cantidad_alumnos', 'vigente_desde']);
            $table->index(['sede_id', 'tipo_cargo_id']);
        });

        // Copia cada tarifa del grupo a todas sus sedes (mismos valores).
        if (Schema::hasTable('tarifas_grupo')) {
            foreach (DB::table('tarifas_grupo')->get() as $tarifa) {
                $sedes = DB::table('sedes')->where('grupo_id', $tarifa->grupo_id)->pluck('id');
                foreach ($sedes as $sedeId) {
                    DB::table('tarifas_sede')->updateOrInsert(
                        [
                            'sede_id' => $sedeId,
                            'tipo_cargo_id' => $tarifa->tipo_cargo_id,
                            'cantidad_alumnos' => $tarifa->cantidad_alumnos,
                            'vigente_desde' => $tarifa->vigente_desde,
                        ],
                        [
                            'monto_por_alumno' => $tarifa->monto_por_alumno,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                    );
                }
            }

            Schema::drop('tarifas_grupo');
        }

        // Cada cargo guarda la sede que cobra (la de su matrícula).
        if (! Schema::hasColumn('cargos', 'sede_id')) {
            Schema::table('cargos', function (Blueprint $table) {
                $table->foreignId('sede_id')->nullable()->after('matricula_id')
                    ->constrained('sedes')->restrictOnDelete();
            });

            // Poblar desde la sede de la matrícula en los cargos existentes.
            foreach (DB::table('cargos')->whereNull('sede_id')->get() as $cargo) {
                $sedeId = DB::table('matriculas')->where('id', $cargo->matricula_id)->value('sede_id');
                if ($sedeId) {
                    DB::table('cargos')->where('id', $cargo->id)->update(['sede_id' => $sedeId]);
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('cargos', 'sede_id')) {
            Schema::table('cargos', function (Blueprint $table) {
                $table->dropConstrainedForeignId('sede_id');
            });
        }

        Schema::create('tarifas_grupo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_id')->constrained('grupos')->cascadeOnDelete();
            $table->foreignId('tipo_cargo_id')->constrained('tipos_cargo')->cascadeOnDelete();
            $table->unsignedSmallInteger('cantidad_alumnos')->default(1);
            $table->unsignedInteger('monto_por_alumno');
            $table->date('vigente_desde')->nullable();
            $table->timestamps();
            $table->index(['grupo_id', 'tipo_cargo_id']);
        });

        Schema::dropIfExists('tarifas_sede');
    }
};
