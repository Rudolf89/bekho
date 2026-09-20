<?php

namespace Database\Seeders;

use App\Models\Cuestionario;
use App\Models\EtapaPrograma;
use App\Models\Federacion;
use App\Models\Grado;
use App\Models\Programa;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Etapas del programa Legacy desde legacy_niveles.json (3 etapas de 100 h, edades
 * 13/16/18, grado mínimo 1.er Dan en la etapa 3) con sus requisitos, y ancla todos
 * los programas a la federación. Idempotente.
 */
class EtapasProgramaSeeder extends Seeder
{
    public function run(): void
    {
        $federacion = Federacion::query()->orderBy('id')->first();
        if (! $federacion) {
            return;
        }

        Programa::query()->whereNull('federacion_id')->update(['federacion_id' => $federacion->id]);

        $this->sembrarLegacy();
    }

    private function sembrarLegacy(): void
    {
        $legacy = Programa::query()->where('nombre', 'Legacy')->first();
        $ruta = database_path('data/legacy_niveles.json');

        if (! $legacy || ! File::exists($ruta)) {
            return;
        }

        $datos = json_decode(File::get($ruta), true);
        $fuente = $datos['fuente'] ?? null;

        $primerDan = Grado::query()->where('nombre', '1º Dan')->first();
        $pruebaN3 = Cuestionario::query()->where('titulo', 'Examen escrito · Programa Legacy Nivel 3')->first();

        $legacy->update(['fuente' => $fuente, 'verificado' => true]);

        foreach ($datos['niveles'] ?? [] as $n) {
            $etapa = EtapaPrograma::updateOrCreate(
                ['programa_id' => $legacy->id, 'nombre' => $n['nombre']],
                [
                    'orden' => $n['orden'],
                    'horas_requeridas' => $n['horas_requeridas'] ?? null,
                    'edad_minima' => $n['edad_minima'] ?? null,
                    'grado_minimo_id' => ! empty($n['grado_minimo']) ? $primerDan?->id : null,
                    'activo' => true,
                    'fuente' => $fuente,
                    'verificado' => true,
                ],
            );

            $orden = 0;
            foreach ($n['requisitos'] ?? [] as $texto) {
                $orden++;
                $esEscrito = stripos($texto, 'escrito') !== false;

                $etapa->requisitos()->updateOrCreate(
                    ['descripcion' => $texto],
                    [
                        'tipo' => $esEscrito ? 'cuestionario' : 'manual',
                        'cuestionario_id' => $esEscrito ? $pruebaN3?->id : null,
                        'orden' => $orden,
                    ],
                );
            }

            if ($pruebaN3 && ! empty($n['examen_escrito'])) {
                $pruebaN3->update(['etapa_programa_id' => $etapa->id]);
            }
        }
    }
}
