<?php

namespace Database\Seeders;

use App\Enums\CategoriaTecnica;
use App\Models\Tecnica;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Paso a paso de las formas Songahm (mano vacía), transcrito del Manual ATA
 * Legacy (currículo de referencia). Cada forma es una técnica de categoría
 * "forma"; aquí se cargan sus movimientos en pasos_tecnica.
 *
 * Los datos viven en database/data/formas_songahm.json (nombre de forma =>
 * lista de movimientos). Debe correr DESPUÉS de TecnicasSeeder. Idempotente:
 * regenera los pasos de cada forma en cada corrida.
 */
class FormasPasosSeeder extends Seeder
{
    public function run(): void
    {
        $ruta = database_path('data/formas_songahm.json');

        if (! File::exists($ruta)) {
            $this->command?->warn("No existe {$ruta}; se omiten los pasos de formas.");

            return;
        }

        /** @var array<string, list<array{lado: string, tecnica: string, postura: string, seccion: string}>> $formas */
        $formas = json_decode(File::get($ruta), true);

        foreach ($formas as $nombre => $pasos) {
            $tecnica = Tecnica::where('categoria', CategoriaTecnica::Forma->value)
                ->where('nombre', $nombre)
                ->first();

            if (! $tecnica) {
                $this->command?->warn("Forma no encontrada en la biblioteca: {$nombre}");

                continue;
            }

            // Descripción: cantidad de movimientos + orientación inicial.
            $tecnica->update([
                'descripcion' => count($pasos).' movimientos. Comienza mirando al Este.',
            ]);

            // Idempotente: se regeneran los pasos.
            $tecnica->pasos()->delete();

            foreach ($pasos as $orden => $paso) {
                $tecnica->pasos()->create([
                    'orden' => $orden + 1,
                    'texto' => $paso['tecnica'],
                    'lado' => $paso['lado'] ?: null,
                    'postura' => $paso['postura'] ?: null,
                    'seccion' => $paso['seccion'] ?: null,
                ]);
            }
        }
    }
}
