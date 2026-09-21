<?php

namespace App\Livewire\Cuestionarios;

use App\Models\Cuestionario;
use App\Models\OpcionPregunta;
use App\Models\PreguntaCuestionario;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Editor de cuestionarios para el examinador (permiso "gestionar cuestionarios").
 * Crea o edita un cuestionario con sus preguntas y opciones. Todo el árbol se
 * mantiene en memoria y se persiste (reconciliando altas/bajas) al guardar.
 */
#[Title('Editar cuestionario')]
class EditarCuestionario extends Component
{
    public ?Cuestionario $cuestionario = null;

    // Metadatos.
    public string $titulo = '';

    public string $descripcion = '';

    public string $area = '';

    public int $umbral_aprobacion = 80;

    public bool $activo = true;

    /**
     * Árbol editable de preguntas con sus opciones.
     *
     * @var list<array{id: int|null, enunciado: string, explicacion: string, nota: string,
     *     opciones: list<array{id: int|null, texto: string, correcta: bool}>}>
     */
    public array $preguntas = [];

    public function mount(?Cuestionario $cuestionario = null): void
    {
        if ($cuestionario && $cuestionario->exists) {
            $this->cuestionario = $cuestionario->load('preguntas.opciones');
            $this->titulo = $cuestionario->titulo;
            $this->descripcion = (string) $cuestionario->descripcion;
            $this->area = (string) $cuestionario->area;
            $this->umbral_aprobacion = $cuestionario->umbral_aprobacion;
            $this->activo = $cuestionario->activo;

            $this->preguntas = array_values(
                $cuestionario->preguntas->map(fn (PreguntaCuestionario $p) => [
                    'id' => $p->id,
                    'enunciado' => $p->enunciado,
                    'explicacion' => (string) $p->explicacion,
                    'nota' => (string) $p->nota,
                    'opciones' => array_values(
                        $p->opciones->map(fn (OpcionPregunta $o) => [
                            'id' => $o->id,
                            'texto' => $o->texto,
                            'correcta' => $o->correcta,
                        ])->all()
                    ),
                ])->all()
            );
        }

        if ($this->preguntas === []) {
            $this->agregarPregunta();
        }
    }

    public function agregarPregunta(): void
    {
        $this->preguntas[] = [
            'id' => null,
            'enunciado' => '',
            'explicacion' => '',
            'nota' => '',
            'opciones' => [
                ['id' => null, 'texto' => '', 'correcta' => false],
                ['id' => null, 'texto' => '', 'correcta' => false],
            ],
        ];
    }

    public function eliminarPregunta(int $i): void
    {
        array_splice($this->preguntas, $i, 1);
    }

    public function agregarOpcion(int $i): void
    {
        $this->preguntas[$i]['opciones'][] = ['id' => null, 'texto' => '', 'correcta' => false];
    }

    public function eliminarOpcion(int $i, int $j): void
    {
        array_splice($this->preguntas[$i]['opciones'], $j, 1);
    }

    public function guardar(): void
    {
        $this->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'umbral_aprobacion' => ['required', 'integer', 'min:1', 'max:100'],
            'preguntas' => ['required', 'array', 'min:1'],
            'preguntas.*.enunciado' => ['required', 'string'],
            'preguntas.*.opciones' => ['required', 'array', 'min:2'],
            'preguntas.*.opciones.*.texto' => ['required', 'string'],
        ], [
            'titulo.required' => 'El título es obligatorio.',
            'preguntas.*.enunciado.required' => 'Cada pregunta necesita un enunciado.',
            'preguntas.*.opciones.min' => 'Cada pregunta necesita al menos 2 opciones.',
            'preguntas.*.opciones.*.texto.required' => 'Las opciones no pueden estar vacías.',
        ]);

        // Cada pregunta debe tener al menos una opción correcta.
        foreach ($this->preguntas as $i => $p) {
            $tieneCorrecta = collect($p['opciones'])->contains(fn ($o) => $o['correcta']);
            if (! $tieneCorrecta) {
                $this->addError("preguntas.$i.correcta", 'Marca al menos una opción correcta.');

                return;
            }
        }

        DB::transaction(function () {
            $cuestionario = Cuestionario::updateOrCreate(
                ['id' => $this->cuestionario?->id],
                [
                    'titulo' => $this->titulo,
                    'descripcion' => $this->descripcion ?: null,
                    'area' => $this->area ?: null,
                    'umbral_aprobacion' => $this->umbral_aprobacion,
                    'activo' => $this->activo,
                    'orden' => $this->cuestionario->orden ?? 0,
                ],
            );

            $idsPreguntas = [];

            foreach ($this->preguntas as $orden => $p) {
                $pregunta = $cuestionario->preguntas()->updateOrCreate(
                    ['id' => $p['id']],
                    [
                        'enunciado' => $p['enunciado'],
                        'explicacion' => $p['explicacion'] ?: null,
                        'nota' => $p['nota'] ?: null,
                        'orden' => $orden,
                    ],
                );
                $idsPreguntas[] = $pregunta->id;

                $idsOpciones = [];
                foreach ($p['opciones'] as $ordenOp => $o) {
                    $opcion = $pregunta->opciones()->updateOrCreate(
                        ['id' => $o['id']],
                        [
                            'texto' => $o['texto'],
                            'correcta' => (bool) $o['correcta'],
                            'orden' => $ordenOp,
                        ],
                    );
                    $idsOpciones[] = $opcion->id;
                }
                // Baja de opciones eliminadas.
                $pregunta->opciones()->whereNotIn('id', $idsOpciones)->delete();
            }

            // Baja de preguntas eliminadas.
            $cuestionario->preguntas()->whereNotIn('id', $idsPreguntas)->delete();

            $this->cuestionario = $cuestionario;
        });

        Flux::toast(variant: 'success', text: 'Cuestionario guardado.');

        $this->redirect(route('cuestionarios.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.cuestionarios.editar-cuestionario');
    }
}
