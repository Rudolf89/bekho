<?php

namespace Database\Seeders;

use App\Enums\NivelEntrenamiento;
use App\Models\Federacion;
use App\Models\TramoEntrenamiento;
use Illuminate\Database\Seeder;

/**
 * Siembra los tramos de entrenamiento de la federación BEKHO a partir del enum
 * NivelEntrenamiento (misma clave, nombre y color), para que el catálogo quede
 * alineado con la operación actual. Idempotente.
 */
class TramosEntrenamientoSeeder extends Seeder
{
    public function run(): void
    {
        $federacion = Federacion::firstOrCreate(
            ['nombre' => 'BEKHO'],
            ['razon_social' => 'BEKHO Martial Arts', 'pais' => 'Chile', 'moneda' => 'CLP', 'activo' => true],
        );

        foreach (NivelEntrenamiento::cases() as $orden => $nivel) {
            TramoEntrenamiento::updateOrCreate(
                ['federacion_id' => $federacion->id, 'clave' => $nivel->value],
                [
                    'nombre' => $nivel->etiqueta(),
                    'orden' => $orden + 1,
                    'color' => $nivel->color(),
                    'activo' => true,
                ],
            );
        }
    }
}
