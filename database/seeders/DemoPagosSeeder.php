<?php

namespace Database\Seeders;

use App\Enums\EstadoPago;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\TarifaGrupo;
use App\Models\TipoCargo;
use App\Services\ServicioCargos;
use App\Services\ServicioPagos;
use App\Support\Tenancy\Grupo as Tenant;
use Illuminate\Database\Seeder;

/**
 * Cobros de demostración por matrícula: fija una tarifa de mensualidad, genera
 * los cargos del mes y paga (verificado) dos tercios; el resto queda con el cargo
 * pendiente (moroso). Idempotente.
 */
class DemoPagosSeeder extends Seeder
{
    public function run(): void
    {
        $grupo = Grupo::where('nombre', 'BEKHO Power Academy')->first();
        if (! $grupo) {
            return;
        }

        Tenant::set($grupo->id);

        $mensualidad = TipoCargo::where('recurrente', true)->orderBy('orden')->first();
        if ($mensualidad) {
            TarifaGrupo::withoutGlobalScopes()->updateOrCreate(
                ['grupo_id' => $grupo->id, 'tipo_cargo_id' => $mensualidad->id, 'cantidad_alumnos' => 1],
                ['monto_por_alumno' => 35000],
            );
        }

        app(ServicioCargos::class)->generarMensualidades();

        $pagos = app(ServicioPagos::class);
        $matriculas = Matricula::withoutGlobalScopes()->activas()->orderBy('id')->get();

        foreach ($matriculas as $k => $matricula) {
            if ($k % 3 === 0) {
                continue; // un tercio queda moroso
            }

            // Idempotente: solo paga si aún hay cargo pendiente.
            if ($pagos->estaMoroso($matricula)) {
                $pagos->registrarPago($matricula, 35000, now(), ['estado' => EstadoPago::Verificado]);
            }
        }

        Tenant::olvidar();
    }
}
