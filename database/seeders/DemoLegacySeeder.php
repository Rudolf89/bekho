<?php

namespace Database\Seeders;

use App\Enums\EstadoLegacy;
use App\Models\InscripcionPrograma;
use App\Models\Persona;
use App\Models\Programa;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Inscripción de demostración en el Programa Legacy, para ver "vivo" el avance
 * del track de formación de instructores: la primera instructora del grupo demo
 * con los Niveles 1 y 2 ya aprobados y el Nivel 3 en curso.
 *
 * Datos ficticios (como el resto de los Demo*); quitar en producción. Los
 * niveles, las horas exigidas y los requisitos NO se inventan aquí: salen del
 * Manual Legacy vía EtapasProgramaSeeder.
 */
class DemoLegacySeeder extends Seeder
{
    /**
     * Cuántos requisitos del nivel en curso se dan por cumplidos en la demo.
     */
    private const REQUISITOS_CUMPLIDOS = 7;

    public function run(): void
    {
        $programa = Programa::where('nombre', 'Legacy')->first();
        $persona = $this->trainee();

        if (! $programa || ! $persona) {
            return;
        }

        $niveles = $programa->etapas()
            ->whereNotNull('horas_requeridas')
            ->orderBy('orden')
            ->get();

        if ($niveles->count() < 3) {
            return;
        }

        [$nivel1, $nivel2, $nivel3] = [$niveles[0], $niveles[1], $niveles[2]];

        $inscripcion = InscripcionPrograma::updateOrCreate(
            ['persona_id' => $persona->id, 'programa_id' => $programa->id],
            [
                'etapa_actual_id' => $nivel3->id,
                'estado' => EstadoLegacy::EnCurso->value,
                'fecha_ingreso' => now()->subYears(3)->toDateString(),
            ],
        );

        // Idempotente: se rehacen las horas, los ascensos y los cumplimientos.
        $inscripcion->horas()->delete();
        $inscripcion->ascensos()->delete();
        $inscripcion->cumplimientos()->delete();

        foreach ([$nivel1, $nivel2] as $indice => $nivel) {
            $inscripcion->ascensos()->create([
                'etapa_programa_id' => $nivel->id,
                'fecha' => now()->subYears(2 - $indice)->toDateString(),
            ]);
        }

        // El tercer bloque de 100 h, repartido en tramos mensuales.
        foreach (range(1, 10) as $mes) {
            $inscripcion->horas()->create([
                'fecha' => now()->subMonths($mes)->toDateString(),
                'horas' => 10,
                'origen' => 'manual',
                'descripcion' => 'Asistencia como instructora en formación (demo)',
            ]);
        }

        $manuales = $nivel3->requisitos()->where('tipo', 'manual')->orderBy('orden')->take(self::REQUISITOS_CUMPLIDOS)->get();

        foreach ($manuales as $requisito) {
            $inscripcion->cumplimientos()->create([
                'requisito_etapa_id' => $requisito->id,
                'cumplido_at' => now()->subMonths(2),
            ]);
        }
    }

    /**
     * Persona de la primera instructora del grupo demo.
     */
    private function trainee(): ?Persona
    {
        $user = User::where('email', 'instructor1@bekho.cl')->first();

        return $user?->persona;
    }
}
