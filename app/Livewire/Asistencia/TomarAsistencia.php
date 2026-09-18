<?php

namespace App\Livewire\Asistencia;

use App\Enums\EstadoAsistencia;
use App\Models\Asistencia;
use App\Models\Clase;
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
    public function marcar(int $estudianteId, string $estado): void
    {
        $clase = $this->claseSeleccionada();

        if (! $clase) {
            return;
        }

        // El estudiante debe pertenecer al roster esperado de la clase.
        if (! $clase->estudiantesEsperados()->whereKey($estudianteId)->exists()) {
            return;
        }

        $estadoEnum = EstadoAsistencia::from($estado);

        Asistencia::updateOrCreate(
            [
                'clase_id' => $clase->id,
                'estudiante_id' => $estudianteId,
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
        $porDia = $clases->groupBy(fn (Clase $c) => $c->dia_semana->value);

        $dias = [];

        for ($i = 0; $i < 7; $i++) {
            $fecha = $lunes->copy()->addDays($i);
            $diaSemana = (int) $fecha->dayOfWeekIso; // 1 = lunes … 7 = domingo

            $clasesDelDia = ($porDia[$diaSemana] ?? collect())
                ->sortBy('hora_inicio')
                ->map(function (Clase $c) use ($fecha) {
                    $esperados = $c->estudiantesEsperados()->count();
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
        $clases = Clase::activas()->with(['sede', 'instructores'])->get();

        $clase = $this->claseSeleccionada();
        $roster = $clase ? $clase->estudiantesEsperados()->get() : collect();

        // Mapa estudiante_id => estado, para la clase y fecha actuales.
        $estados = [];
        if ($clase) {
            $estados = Asistencia::where('clase_id', $clase->id)
                ->whereDate('fecha', $this->fecha)
                ->pluck('estado', 'estudiante_id')
                ->map(fn ($e) => $e instanceof EstadoAsistencia ? $e->value : $e)
                ->all();
        }

        $presentes = collect($estados)->filter(fn ($e) => $e === EstadoAsistencia::Presente->value)->count();

        $lunes = Carbon::parse($this->semanaInicio);

        return view('livewire.asistencia.tomar-asistencia', [
            'dias' => $this->armarDias($clases),
            'rangoSemana' => $lunes->locale('es')->isoFormat('D [de] MMMM').' – '.$lunes->copy()->addDays(6)->locale('es')->isoFormat('D [de] MMMM'),
            'clase' => $clase,
            'fechaLista' => Carbon::parse($this->fecha)->locale('es')->isoFormat('dddd D [de] MMMM'),
            'roster' => $roster,
            'estados' => $estados,
            'presentes' => $presentes,
        ]);
    }
}
