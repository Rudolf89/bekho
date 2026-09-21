<?php

namespace Database\Seeders;

use App\Models\AtributoTecnico;
use App\Models\EscalaPuntaje;
use App\Models\Federacion;
use App\Models\InstrumentoEvaluacion;
use App\Models\SeccionInstrumento;
use Illuminate\Database\Seeder;

/**
 * Instrumentos de evaluación práctica de la federación (idempotentes). Siembra:
 *  - Prueba de planillero: escala Rúbrica (0–6.0), aprueba con 80 %, tres
 *    secciones (Fórmula y Armas, Sparring, Recuento de medallas) con el
 *    enunciado del caso y los ítems de la rúbrica transcritos del xlsx oficial.
 *  - Evaluación de formas y patadas: escala Competencia (9.1–9.9), nota mínima
 *    9.5; criterios = los 10 atributos técnicos + los 3 criterios de conocimiento
 *    de forma, ya sembrados en atributos_tecnicos.
 */
class InstrumentosEvaluacionSeeder extends Seeder
{
    /** Nota mínima de aprobación de la escala de competencia (reglamento). */
    private const NOTA_APROBACION = 9.5;

    /** Procedencia de la rúbrica de planillero: el xlsx oficial de la planilla. */
    private const FUENTE_PLANILLERO = 'Planilla de competencia oficial BEKHO (prueba de planillero)';

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
                'fuente' => self::FUENTE_PLANILLERO,
                'verificado' => true,
            ],
        );

        foreach ($this->rubricaPlanillero() as $orden => $seccionRubrica) {
            $seccion = $instrumento->secciones()->updateOrCreate(
                ['nombre' => $seccionRubrica['nombre']],
                ['enunciado' => $seccionRubrica['enunciado'], 'orden' => $orden + 1],
            );

            $vigentes = [];

            foreach ($seccionRubrica['criterios'] as $i => [$nombre, $valor]) {
                $vigentes[] = $seccion->criterios()->updateOrCreate(
                    ['nombre' => $nombre],
                    ['valor' => $valor, 'orden' => $i + 1],
                )->id;
            }

            $this->retiraCriteriosObsoletos($seccion, $vigentes);
        }
    }

    /**
     * Retira los criterios de la sección que ya no están en la rúbrica (los de
     * relleno que se sembraban antes de tener el xlsx).
     *
     * Un criterio con puntajes ya registrados NO se borra: `puntajes_criterio`
     * cuelga en cascada y se perdería el historial de esa evaluación. En ese
     * caso se deja como está y se avisa por consola para decidirlo a mano.
     *
     * @param  list<int>  $vigentes
     */
    private function retiraCriteriosObsoletos(SeccionInstrumento $seccion, array $vigentes): void
    {
        $obsoletos = $seccion->criterios()->whereNotIn('id', $vigentes)->get();

        foreach ($obsoletos as $criterio) {
            if ($criterio->puntajes()->exists()) {
                $this->command->warn(sprintf(
                    'Prueba de planillero · %s: el criterio "%s" ya no está en la rúbrica del xlsx, '
                    .'pero tiene puntajes registrados y NO se borró. Revisar a mano.',
                    $seccion->nombre,
                    $criterio->nombre,
                ));

                continue;
            }

            $criterio->delete();
        }
    }

    /**
     * Rúbrica de la prueba de planillero, transcrita LITERAL del xlsx oficial:
     * el enunciado del caso de cada sección y cada ítem con su valor en puntos.
     *
     * El enunciado se arma con los mismos textos de los ítems (así no se pueden
     * desfasar): en la planilla el caso ES la pauta de corrección — el planillero
     * gana el puntaje del ítem si registró bien ese dato o ese incidente.
     *
     * OJO: la numeración del xlsx salta de "f.-" a "h.-" (no existe un "g.-").
     * Se transcribe tal cual; ni se renumera ni se inventa el ítem que falta.
     * Los valores suman 6,0, el `PUNTAJE MÁXIMO` que declara la propia prueba.
     *
     * @return list<array{nombre: string, enunciado: string, criterios: list<array{string, float}>}>
     */
    private function rubricaPlanillero(): array
    {
        /** @var list<array{string, list<array{string, list<array{string, float}>}>}> $secciones */
        $secciones = [
            ['Fórmula y Armas', [
                ['1. INFORMACIÓN GENERAL', [
                    ['11 participantes.', 0.1],
                    ['6 además participan en Arma .', 0.1],
                    ['Fecha al día, Hora de competencia 14:00 hrs. considerando 1:30 horas de duración.', 0.1],
                    ['Cinturones Negros Categoría 1BD.', 0.1],
                    ['Edades entre 30 y 39 años.', 0.1],
                    ['Categoría Masculina.', 0.1],
                    ['Pista nº 8.', 0.1],
                    ['Usted es el Planillero.', 0.1],
                    ['Cree nombres ficticios tanto para los jueces', 0.1],
                    ['Cree nombres ficticios para competidores.', 0.1],
                ]],
                ['2.', [
                    ['a.- (Competencia de Fórmula) El 5to participante se salta 4 pasos consecutivos en su fórmula.', 0.5],
                    ['b.- (Competencia de Fórmula) hay un empate por el primer lugar.', 0.5],
                    ['c.- (Competencia de Armas) Los dos primeros competidores en presentarse han empatado por el 1er lugar de armas, y durante el desempate al primero en presentar se le cae el arma, y el segundo se saltó 2 pasos.', 0.5],
                ]],
            ]],
            ['Sparring', [
                ['', [
                    ['d.- (Competencia de Sparring) en la primera ronda un participante recibe 2 advertencias de No contacto.', 0.5],
                    ['e.- (Competencia de Sparring) en la segunda ronda una participante recibe 3 advertencias. Dos de ellas son penalidades de contacto.', 0.5],
                    ['f.- (Competencia de Sparring) en la semifinal, una participante recibe un punto (legal) que no le permite seguir en competencia.', 0.5],
                    ['h.-Usted genere los puntajes de las participantes por cada ronda y complete la planilla totalmente. 1 pto.', 1.0],
                ]],
            ]],
            ['Recuento de medallas', [
                ['', [
                    ['REQUERIMIENTO DE MEDALLAS', 1.0],
                ]],
            ]],
        ];

        return array_map(fn (array $seccion) => [
            'nombre' => $seccion[0],
            'enunciado' => $this->enunciado($seccion[1]),
            'criterios' => array_merge(...array_map(fn (array $bloque) => $bloque[1], $seccion[1])),
        ], $secciones);
    }

    /**
     * Enunciado de una sección: los bloques del xlsx con su título (cuando lo
     * tienen) y los ítems, uno por línea y en el orden de la planilla.
     *
     * @param  list<array{string, list<array{string, float}>}>  $bloques
     */
    private function enunciado(array $bloques): string
    {
        return implode("\n\n", array_map(function (array $bloque) {
            [$titulo, $items] = $bloque;
            $lineas = array_map(fn (array $item) => $item[0], $items);

            return implode("\n", $titulo === '' ? $lineas : [$titulo, ...$lineas]);
        }, $bloques));
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
