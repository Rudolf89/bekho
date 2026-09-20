<?php

namespace App\Livewire\Estudiantes;

use App\Models\Grado;
use App\Models\Matricula;
use App\Models\NotaMatricula;
use App\Services\ServicioExamenes;
use App\Services\ServicioPagos;
use Flux\Flux;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Ficha del alumno: identidad, grado y progreso al siguiente cinturón, historial
 * de graduaciones, asistencia del ciclo, estado de cuenta y notas del instructor.
 */
#[Title('Ficha del alumno')]
class VerMatricula extends Component
{
    use AuthorizesRequests;

    public Matricula $matricula;

    public string $nuevaNota = '';

    public function mount(Matricula $matricula): void
    {
        $this->authorize('view', $matricula);
        $this->matricula = $matricula;
    }

    public function agregarNota(): void
    {
        $this->authorize('update', $this->matricula);
        $this->validate(['nuevaNota' => ['required', 'string', 'max:1000']]);

        NotaMatricula::create([
            'grupo_id' => $this->matricula->grupo_id,
            'matricula_id' => $this->matricula->id,
            'autor_user_id' => Auth::id(),
            'cuerpo' => $this->nuevaNota,
        ]);

        $this->reset('nuevaNota');
        Flux::toast(variant: 'success', text: 'Nota guardada.');
    }

    /**
     * Grado siguiente en la escala del alumno (el próximo por orden), o null si ya
     * está en el tope.
     */
    protected function gradoSiguiente(): ?Grado
    {
        $actual = $this->matricula->persona?->grado;

        return Grado::porEscala($this->matricula->escalaGrado())
            ->when($actual, fn ($q) => $q->where('orden', '>', $actual->orden))
            ->ordenados()
            ->first();
    }

    public function render(ServicioExamenes $examenes, ServicioPagos $pagos)
    {
        $this->matricula->load(['persona.grado', 'sede', 'notas.autor',
            'graduaciones.gradoDestino']);

        $actual = $this->matricula->persona?->grado;
        $siguiente = $this->gradoSiguiente();

        // Progreso al siguiente cinturón: meses en el grado vs meses sugeridos.
        $meses = $examenes->mesesEnGradoActual($this->matricula);
        $sugeridos = $actual?->meses_sugeridos ?: null;
        $progreso = $sugeridos ? min(100, (int) round($meses / $sugeridos * 100)) : null;

        $pendientes = $pagos->cargosPendientes($this->matricula);

        return view('livewire.estudiantes.ver-matricula', [
            'gradoActual' => $actual,
            'gradoSiguiente' => $siguiente,
            'mesesEnGrado' => $meses,
            'mesesSugeridos' => $sugeridos,
            'progreso' => $progreso,
            'asistencia' => $examenes->porcentajeAsistencia($this->matricula),
            'graduaciones' => $this->matricula->graduaciones->sortByDesc('fecha'),
            'tecnicasSiguiente' => $siguiente ? $siguiente->tecnicas()->orderBy('nombre')->get() : collect(),
            'cargosPendientes' => $pendientes,
            'deuda' => (int) $pendientes->sum('monto'),
            'moroso' => $pagos->estaMoroso($this->matricula),
            'puedeEditar' => Auth::user()?->can('update', $this->matricula),
        ]);
    }
}
