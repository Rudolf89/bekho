<?php

namespace Database\Seeders;

use App\Enums\EscalaGrado;
use App\Models\Convocatoria;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\User;
use App\Services\ServicioCreditos;
use Illuminate\Database\Seeder;

/**
 * Convocatoria de examen e historial de graduaciones de demostración, por
 * matrícula. Corre después de MigraPersonasSeeder. Idempotente.
 */
class DemoExamenesSeeder extends Seeder
{
    public function run(): void
    {
        $grupo = Grupo::where('nombre', 'BEKHO Power Academy')->first();
        if (! $grupo) {
            return;
        }

        $rodolfo = User::where('email', 'rodolfo@bekho.cl')->first();
        $sede = $grupo->sedes()->where('nombre', 'BEKHO Central')->first();

        $matriculas = Matricula::withoutGlobalScopes()->activas()->where('grupo_id', $grupo->id)->orderBy('id')->get();

        // Convocatoria próxima con inscritos.
        $conv = Convocatoria::updateOrCreate(
            ['grupo_id' => $grupo->id, 'nombre' => 'Examen de grado'],
            ['sede_id' => $sede?->id, 'fecha' => now()->addDays(9)->toDateString(), 'estado' => 'programada'],
        );
        foreach ($matriculas->take(9) as $matricula) {
            $conv->inscripciones()->updateOrCreate(
                ['matricula_id' => $matricula->id],
                ['grupo_id' => $grupo->id, 'grado_origen_id' => $matricula->persona?->grado_id, 'instructor_id' => $rodolfo?->id],
            );
        }

        // Historial de graduaciones: el crédito lo recibe el instructor acreditado
        // (origen resuelto por la matrícula) y toda su cadena de supervisión.
        $grado = Grado::porEscala(EscalaGrado::Adultos)->ordenados()->first();
        $creditos = app(ServicioCreditos::class);
        foreach ($matriculas->take(14)->values() as $m => $matricula) {
            $graduacion = $matricula->graduaciones()->updateOrCreate(
                ['convocatoria_id' => null, 'grado_destino_id' => $grado?->id],
                [
                    'grupo_id' => $grupo->id,
                    'fecha' => now()->subMonths($m % 6 + 1)->toDateString(),
                    'resultado' => 'aprobado',
                ],
            );
            $creditos->otorgar($graduacion);
        }
    }
}
