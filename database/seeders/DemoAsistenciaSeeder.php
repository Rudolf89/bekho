<?php

namespace Database\Seeders;

use App\Enums\EstadoAsistencia;
use App\Models\Asistencia;
use App\Models\Clase;
use Illuminate\Database\Seeder;

/**
 * Asistencia de demostración de HOY, por matrícula. Corre después de
 * MigraPersonasSeeder (que crea las matrículas). Marca presente a la mitad del
 * roster de cada clase con horario hoy. Idempotente.
 */
class DemoAsistenciaSeeder extends Seeder
{
    public function run(): void
    {
        $diaHoy = (int) now()->dayOfWeekIso;

        $clasesHoy = Clase::whereHas('horarios', fn ($q) => $q->where('dia_semana', $diaHoy))->get();

        foreach ($clasesHoy as $clase) {
            foreach ($clase->matriculasEsperadas()->get()->values() as $j => $matricula) {
                Asistencia::updateOrCreate(
                    ['clase_id' => $clase->id, 'matricula_id' => $matricula->id, 'fecha' => now()->toDateString()],
                    ['grupo_id' => $clase->grupo_id, 'estado' => $j % 2 === 0 ? EstadoAsistencia::Presente : EstadoAsistencia::Ausente],
                );
            }
        }
    }
}
