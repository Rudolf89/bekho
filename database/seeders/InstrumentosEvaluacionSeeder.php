<?php

namespace Database\Seeders;

use App\Models\AtributoTecnico;
use App\Models\EscalaPuntaje;
use App\Models\Federacion;
use App\Models\InstrumentoEvaluacion;
use Illuminate\Database\Seeder;

/**
 * Instrumentos de evaluación práctica de la federación (idempotentes). Siembra:
 *  - Prueba de planillero: escala Rúbrica (0–6.0), aprueba con 80 %, tres
 *    secciones (Fórmula y Armas, Sparring, Recuento de medallas).
 *  - Evaluación de formas y patadas: escala Competencia (9.1–9.9), nota mínima
 *    9.5; criterios = los 10 atributos técnicos + los 3 criterios de conocimiento
 *    de forma, ya sembrados en atributos_tecnicos.
 */
class InstrumentosEvaluacionSeeder extends Seeder
{
    /** Nota mínima de aprobación de la escala de competencia (reglamento). */
    private const NOTA_APROBACION = 9.5;

    public function run(): void
    {
        $federacion = Federacion::query()->orderBy('id')->first();
        if (! $federacion) {
            return;
        }

        $rubrica = EscalaPuntaje::where('federacion_id', $federacion->id)->where('nombre', 'Rúbrica')->first();
        $competencia = EscalaPuntaje::where('federacion_id', $federacion->id)->where('nombre', 'Competencia')->first();

        $this->pruebaPlanillero($federacion, $rubrica);
        $this->evaluacionFormasPatadas($federacion, $competencia);
    }

    private function pruebaPlanillero(Federacion $federacion, ?EscalaPuntaje $rubrica): void
    {
        $instrumento = InstrumentoEvaluacion::updateOrCreate(
            ['federacion_id' => $federacion->id, 'nombre' => 'Prueba de planillero'],
            [
                'escala_id' => $rubrica?->id,
                'puntaje_maximo' => 6.0,
                'umbral_porcentaje' => 80,
                'nota_minima' => null,
                'activo' => true,
                'orden' => 1,
                // Prueba definida por BEKHO; sin fuente documental → sin verificar.
                'verificado' => false,
            ],
        );

        // Cada sección lleva un criterio puntuable homónimo (el manual no detalla
        // sub-criterios; no se inventan).
        $secciones = ['Fórmula y Armas', 'Sparring', 'Recuento de medallas'];
        foreach ($secciones as $orden => $nombre) {
            $seccion = $instrumento->secciones()->updateOrCreate(
                ['nombre' => $nombre],
                ['orden' => $orden + 1],
            );
            $seccion->criterios()->updateOrCreate(['nombre' => $nombre], ['orden' => 1]);
        }
    }

    private function evaluacionFormasPatadas(Federacion $federacion, ?EscalaPuntaje $competencia): void
    {
        $instrumento = InstrumentoEvaluacion::updateOrCreate(
            ['federacion_id' => $federacion->id, 'nombre' => 'Evaluación de formas y patadas'],
            [
                'escala_id' => $competencia?->id,
                'puntaje_maximo' => 9.9,
                'umbral_porcentaje' => null,
                'nota_minima' => self::NOTA_APROBACION,
                'activo' => true,
                'orden' => 2,
                'fuente' => ManualLegacySeeder::FUENTE,
                'verificado' => true,
            ],
        );

        $seccion = $instrumento->secciones()->updateOrCreate(
            ['nombre' => 'Rúbrica técnica'],
            ['orden' => 1],
        );

        // Los 10 atributos + los 3 criterios de conocimiento de forma, en orden.
        $atributos = AtributoTecnico::where('federacion_id', $federacion->id)
            ->orderByRaw("case tipo when 'atributo' then 0 else 1 end")
            ->orderBy('orden')
            ->get();

        foreach ($atributos as $i => $atributo) {
            $seccion->criterios()->updateOrCreate(
                ['atributo_tecnico_id' => $atributo->id],
                ['nombre' => $atributo->nombre, 'orden' => $i + 1],
            );
        }
    }
}
