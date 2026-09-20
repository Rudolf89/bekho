<?php

namespace Database\Seeders;

use App\Enums\EstadoPago;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Sede;
use App\Models\TarifaSede;
use App\Models\TipoCargo;
use App\Services\ServicioCargos;
use App\Services\ServicioPagos;
use App\Support\Tenancy\Grupo as Tenant;
use Illuminate\Database\Seeder;

/**
 * Cobros de demostración por sede: fija una tarifa de mensualidad en cada sede del
 * grupo, genera los cargos del mes y paga (verificado) dos tercios; el resto queda
 * con el cargo pendiente (moroso). Idempotente.
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
            // Cada sede define su tarifa; en la demo todas parten en 35.000.
            foreach (Sede::where('grupo_id', $grupo->id)->get() as $sede) {
                TarifaSede::updateOrCreate(
                    ['sede_id' => $sede->id, 'tipo_cargo_id' => $mensualidad->id, 'cantidad_alumnos' => 1, 'vigente_desde' => null],
                    ['monto_por_alumno' => 35000],
                );
            }
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
