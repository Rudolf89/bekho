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
 * 13/16/18, grado mínimo 1.er Dan en la etapa 3) con sus requisitos, más los
 * requisitos Protech por nivel desde armas_protech.json, y ancla todos los
 * programas a la federación. Idempotente.
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
        $this->sembrarProtech();
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
                        // Los requisitos salen del mismo manual (el JSON declara su fuente).
                        'fuente' => $fuente,
                        'verificado' => true,
                    ],
                );
            }

            if ($pruebaN3 && ! empty($n['examen_escrito'])) {
                $pruebaN3->update(['etapa_programa_id' => $etapa->id]);
            }
        }
    }

    /**
     * Requisitos Protech (armas) por nivel Legacy, desde armas_protech.json. Se
     * SUMAN a los requisitos de legacy_niveles.json (no los tocan), verificados y
     * con su fuente. El arma va en el texto (requisitos_etapa no tiene relación a
     * armas). Idempotente por (etapa, descripción).
     */
    private function sembrarProtech(): void
    {
        $legacy = Programa::query()->where('nombre', 'Legacy')->first();
        $ruta = database_path('data/armas_protech.json');

        if (! $legacy || ! File::exists($ruta)) {
            return;
        }

        $datos = json_decode(File::get($ruta), true);
        $fuente = $datos['fuente'] ?? null;

        // Etapas operativas de Legacy indexadas por orden (1, 2, 3).
        $etapasPorOrden = $legacy->etapas()->whereNotNull('horas_requeridas')->get()->keyBy('orden');

        foreach ($datos['requisitos_por_nivel'] ?? [] as $bloque) {
            $etapa = $etapasPorOrden[(int) ($bloque['nivel'] ?? 0)] ?? null;
            if (! $etapa) {
                continue;
            }

            // Los Protech van al final, después de los requisitos del reglamento.
            $orden = 100;

            if (! empty($bloque['condicion_general'])) {
                $this->crearRequisitoProtech($etapa, 'Protech (condición general): '.$bloque['condicion_general'], $orden++, $fuente);
            }

            foreach ($bloque['items'] ?? [] as $item) {
                $arma = $item['arma'] ?? null;
                $detalle = $item['detalle'] ?? null;

                // Donde el manual no da detalle, no se inventa (queda solo el arma).
                $texto = match (true) {
                    $arma && $detalle => "Protech: {$arma} — {$detalle}",
                    (bool) $arma => "Protech: {$arma}",
                    (bool) $detalle => "Protech: {$detalle}",
                    default => 'Protech',
                };

                $this->crearRequisitoProtech($etapa, $texto, $orden++, $fuente);
            }
        }
    }

    private function crearRequisitoProtech(EtapaPrograma $etapa, string $texto, int $orden, ?string $fuente): void
    {
        $etapa->requisitos()->updateOrCreate(
            ['descripcion' => $texto],
            ['tipo' => 'manual', 'cuestionario_id' => null, 'orden' => $orden, 'fuente' => $fuente, 'verificado' => true],
        );
    }
}
