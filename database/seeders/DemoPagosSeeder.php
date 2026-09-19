<?php

namespace Database\Seeders;

use App\Enums\TipoPago;
use App\Models\Matricula;
use App\Models\Pago;
use Illuminate\Database\Seeder;

/**
 * Pagos de demostración del mes por matrícula: dos tercios pagan la mensualidad;
 * el resto queda moroso. Corre después de MigraPersonasSeeder. Idempotente.
 */
class DemoPagosSeeder extends Seeder
{
    public function run(): void
    {
        $matriculas = Matricula::withoutGlobalScopes()->activas()->orderBy('id')->get();

        foreach ($matriculas as $k => $matricula) {
            if ($k % 3 === 0) {
                continue; // un tercio queda moroso
            }

            Pago::updateOrCreate(
                [
                    'matricula_id' => $matricula->id,
                    'tipo' => TipoPago::Mensualidad->value,
                    'periodo' => now()->startOfMonth()->toDateString(),
                ],
                ['grupo_id' => $matricula->grupo_id, 'monto' => 35000, 'fecha_pago' => now()->toDateString()],
            );
        }
    }
}
