<?php

namespace App\Livewire\Cuestionarios;

use App\Models\Cuestionario;
use App\Models\IntentoCuestionario;
use App\Models\PreguntaCuestionario;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Rendir un cuestionario: muestra las preguntas, el usuario responde y al enviar
 * se autocorrige, se calcula el puntaje y se guarda el intento con sus respuestas.
 */
#[Title('Rendir cuestionario')]
class RendirCuestionario extends Component
{
    public Cuestionario $cuestionario;

    /**
     * Respuestas elegidas: [pregunta_id => [opcion_id, ...]].
     *
     * @var array<int, array<int, int>>
     */
    public array $seleccion = [];

    public bool $finalizado = false;

    public int $correctas = 0;

    public int $total = 0;

    public int $porcentaje = 0;

    public bool $aprobado = false;

    public function mount(Cuestionario $cuestionario): void
    {
        abort_unless($cuestionario->activo || Auth::user()->can('gestionar cuestionarios'), 404);

        $this->cuestionario = $cuestionario->load('preguntas.opciones');
    }

    /**
     * Marca/desmarca una opción. Si la pregunta tiene una sola correcta se
     * comporta como radio (reemplaza); si admite varias, como checkbox (acumula).
     */
    public function alternar(int $preguntaId, int $opcionId, bool $multiple): void
    {
        if ($this->finalizado) {
            return;
        }

        if (! $multiple) {
            $this->seleccion[$preguntaId] = [$opcionId];

            return;
        }

        $actual = $this->seleccion[$preguntaId] ?? [];

        if (in_array($opcionId, $actual, true)) {
            $this->seleccion[$preguntaId] = array_values(array_diff($actual, [$opcionId]));
        } else {
            $actual[] = $opcionId;
            $this->seleccion[$preguntaId] = array_values($actual);
        }
    }

    public function enviar(): void
    {
        if ($this->finalizado) {
            return;
        }

        $preguntas = $this->cuestionario->preguntas;
        $this->total = $preguntas->count();
        $this->correctas = 0;

        $intento = null;

        DB::transaction(function () use ($preguntas, &$intento) {
            $intento = IntentoCuestionario::create([
                'user_id' => Auth::id(),
                'cuestionario_id' => $this->cuestionario->id,
                'total' => $this->total,
                'finalizado_at' => now(),
            ]);

            foreach ($preguntas as $pregunta) {
                $elegidas = $this->seleccion[$pregunta->id] ?? [];
                $acierto = $pregunta->esCorrecta($elegidas);

                if ($acierto) {
                    $this->correctas++;
                }

                $respuesta = $intento->respuestas()->create([
                    'pregunta_id' => $pregunta->id,
                    'correcta' => $acierto,
                ]);

                if ($elegidas !== []) {
                    $respuesta->opciones()->sync($elegidas);
                }
            }

            $this->porcentaje = $this->total > 0
                ? (int) round($this->correctas / $this->total * 100)
                : 0;
            $this->aprobado = $this->porcentaje >= $this->cuestionario->umbral_aprobacion;

            $intento->update([
                'correctas' => $this->correctas,
                'porcentaje' => $this->porcentaje,
                'aprobado' => $this->aprobado,
            ]);
        });

        $this->finalizado = true;
    }

    public function reintentar(): void
    {
        $this->reset(['seleccion', 'finalizado', 'correctas', 'total', 'porcentaje', 'aprobado']);
    }

    /**
     * ¿La opción fue elegida por el usuario?
     */
    public function elegida(int $preguntaId, int $opcionId): bool
    {
        return in_array($opcionId, $this->seleccion[$preguntaId] ?? [], true);
    }

    /**
     * ¿Acertó la pregunta? (solo tras finalizar).
     */
    public function aciertoEn(PreguntaCuestionario $pregunta): bool
    {
        return $pregunta->esCorrecta($this->seleccion[$pregunta->id] ?? []);
    }

    public function render()
    {
        return view('livewire.cuestionarios.rendir-cuestionario');
    }
}
