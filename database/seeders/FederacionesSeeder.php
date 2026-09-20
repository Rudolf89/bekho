<?php

namespace Database\Seeders;

use App\Models\Federacion;
use Illuminate\Database\Seeder;

/**
 * Siembra la federación raíz: BEKHO. Idempotente por nombre.
 */
class FederacionesSeeder extends Seeder
{
    public function run(): void
    {
        Federacion::updateOrCreate(
            ['nombre' => 'BEKHO'],
            [
                'razon_social' => 'BEKHO Martial Arts',
                'pais' => 'Chile',
                'moneda' => 'CLP',
                'activo' => true,
            ],
        );
    }
}
