<?php

namespace App\Livewire\Asistencia;

use App\Enums\EstadoAsistencia;
use App\Models\Asistencia;
use App\Models\Clase;
use App\Models\Matricula;
use App\Services\ServicioPagos;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Tomar asistencia')]
class TomarAsistencia extends Component
{
    public ?string $claseId = '';

    /** Fecha de la lista abierta (Y-m-d). */
    public string $fecha = '';

    /** Lunes de la semana mostrada en el calendario (Y-m-d). */
    public string $semanaInicio = '';

    public function mount(): void
    {
        $hoy = now();
        $this->fecha = $hoy->format('Y-m-d');
        $this->semanaInicio = $hoy->copy()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
    }

    public function semanaAnterior(): void
    {
        $this->semanaInicio = Carbon::parse($this->semanaInicio)->subWeek()->format('Y-m-d');
    }

    public function semanaSiguiente(): void
    {
        $this->semanaInicio = Carbon::parse($this->semanaInicio)->addWeek()->format('Y-m-d');
    }

    public function irAHoy(): void
    {
        $this->semanaInicio = now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
    }

    /**
     * Abre la lista de una clase en una fecha concreta (día del calendario).
     */
    public function abrirClase(int $claseId, string $fecha): void
    {
        $clase = Clase::find($claseId);

        if (! $clase) {
            return;
        }

        $this->claseId = (string) $claseId;
        $this->fecha = $fecha;
    }

    /**
     * Cierra la lista y vuelve al calendario.
     */
    public function volver(): void
    {
        $this->claseId = '';
    }

    /**
     * Marca (o cambia) el estado de un estudiante en la clase y fecha actuales.
     * Persiste de inmediato para agilizar la toma en el tatami.
     */
    public function marcar(int $matriculaId, string $estado, ServicioPagos $pagos): void
    {
        $clase = $this->claseSeleccionada();

        if (! $clase) {
            return;
        }

        // La matrícula debe pertenecer al roster esperado de la clase.
        if (! $clase->matriculasEsperadas()->where('matriculas.id', $matriculaId)->exists()) {
            return;
        }

        $estadoEnum = EstadoAsistencia::from($estado);

        // Reglamento: un moroso que ya usó sus 3 clases de gracia tras el
        // vencimiento no puede ingresar hasta regularizar (solo se le marca ausente).
        if ($estadoEnum === EstadoAsistencia::Presente
            && $pagos->estaBloqueadoPorDeuda(Matricula::findOrFail($matriculaId))) {
            Flux::toast(variant: 'warning', text: 'Alumno con deuda: superó las 3 clases de gracia. Debe regularizar su pago para ingresar.');

            return;
        }

        Asistencia::updateOrCreate(
            [
                'clase_id' => $clase->id,
                'matricula_id' => $matriculaId,
                'fecha' => $this->fecha,
            ],
            [
                'grupo_id' => $clase->grupo_id,
                'estado' => $estadoEnum,
                'registrado_por' => Auth::id(),
            ],
        );
    }

    /**
     * Clase seleccionada (respeta el scope por grupo).
     */
    protected function claseSeleccionada(): ?Clase
    {
        return $this->claseId ? Clase::find($this->claseId) : null;
    }

    /**
     * Arma los 7 días de la semana mostrada, cada uno con sus clases y el avance
     * de asistencia (presentes / esperados) en esa fecha concreta.
     *
     * @param  Collection<int, Clase>  $clases
     * @return list<array<string, mixed>>
     */
    protected function armarDias($clases): array
    {
        $lunes = Carbon::parse($this->semanaInicio);
        $hoy = now()->toDateString();

        // Cada clase aparece en cada día en que tiene un horario: se aplanan las
        // clases en pares (clase, horario) agrupados por día de la semana.
        $porDia = $clases
            ->flatMap(fn (Clase $c) => $c->horarios->map(fn ($h) => ['clase' => $c, 'horario' => $h]))
            ->groupBy(fn (array $par) => $par['horario']->dia_semana->value);

        $dias = [];

        for ($i = 0; $i < 7; $i++) {
            $fecha = $lunes->copy()->addDays($i);
            $diaSemana = (int) $fecha->dayOfWeekIso; // 1 = lunes … 7 = domingo

            $clasesDelDia = ($porDia[$diaSemana] ?? collect())
                ->sortBy(fn (array $par) => (string) $par['horario']->hora_inicio)
                ->map(function (array $par) use ($fecha) {
                    $c = $par['clase'];
                    $horario = $par['horario'];
                    $esperados = $c->matriculasEsperadas()->count();
                    $presentes = Asistencia::where('clase_id', $c->id)
                        ->whereDate('fecha', $fecha->toDateString())
                        ->where('estado', EstadoAsistencia::Presente->value)
                        ->count();
                    $tomada = Asistencia::where('clase_id', $c->id)
                        ->whereDate('fecha', $fecha->toDateString())
                        ->exists();

                    $titular = $c->instructores->firstWhere('pivot.papel', 'titular')
                        ?? $c->instructores->first();

                    return [
                        'clase' => $c,
                        'horaInicio' => substr((string) $horario->hora_inicio, 0, 5),
                        'horaFin' => substr((string) $horario->hora_fin, 0, 5),
                        'esperados' => $esperados,
                        'presentes' => $presentes,
                        'tomada' => $tomada,
                        'titular' => $titular?->name,
                    ];
                })
                ->values();

            $dias[] = [
                'fecha' => $fecha->toDateString(),
                'diaNombre' => $fecha->locale('es')->isoFormat('dddd'),
                'diaNumero' => $fecha->day,
                'mes' => $fecha->locale('es')->isoFormat('MMM'),
                'esHoy' => $fecha->toDateString() === $hoy,
                'clases' => $clasesDelDia,
            ];
        }

        return $dias;
    }

    public function render()
    {
        $clases = Clase::activas()->with(['sede', 'instructores', 'horarios'])->get();

        $clase = $this->claseSeleccionada();
        $roster = $clase ? $clase->matriculasEsperadas()->get() : collect();

        // Horario de la clase abierta que corresponde al día de la fecha elegida.
        $horarioLista = null;
        if ($clase) {
            $diaFecha = (int) Carbon::parse($this->fecha)->dayOfWeekIso;
            $h = $clase->horarios->firstWhere(fn ($h) => $h->dia_semana->value === $diaFecha)
                ?? $clase->horarios->first();
            if ($h) {
                $horarioLista = substr((string) $h->hora_inicio, 0, 5).' – '.substr((string) $h->hora_fin, 0, 5);
            }
        }

        // Mapa matricula_id => estado, para la clase y fecha actuales.
        $estados = [];
        if ($clase) {
            $estados = Asistencia::where('clase_id', $clase->id)
                ->whereDate('fecha', $this->fecha)
                ->pluck('estado', 'matricula_id')
                ->map(fn ($e) => $e instanceof EstadoAsistencia ? $e->value : $e)
                ->all();
        }

        $presentes = collect($estados)->filter(fn ($e) => $e === EstadoAsistencia::Presente->value)->count();

        $lunes = Carbon::parse($this->semanaInicio);

        return view('livewire.asistencia.tomar-asistencia', [
            'dias' => $this->armarDias($clases),
            'rangoSemana' => $lunes->locale('es')->isoFormat('D [de] MMMM').' – '.$lunes->copy()->addDays(6)->locale('es')->isoFormat('D [de] MMMM'),
            'clase' => $clase,
            'horarioLista' => $horarioLista,
            'fechaLista' => Carbon::parse($this->fecha)->locale('es')->isoFormat('dddd D [de] MMMM'),
            'roster' => $roster,
            'estados' => $estados,
            'presentes' => $presentes,
        ]);
    }
}
