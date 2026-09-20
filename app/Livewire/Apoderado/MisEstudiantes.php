<?php

namespace App\Livewire\Apoderado;

use App\Enums\EstadoPago;
use App\Models\Matricula;
use App\Services\ServicioPagos;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Vista del apoderado: ve las matrículas de las personas que tutela, su estado de
 * cuenta (cargos pendientes) y puede informar un pago subiendo el comprobante de
 * transferencia (queda "por verificar" hasta que recepción/dirección lo confirme).
 * El aislamiento se apoya en Matricula::visiblePara (tutelas).
 */
#[Title('Mis estudiantes')]
class MisEstudiantes extends Component
{
    use WithFileUploads;

    // Informar pago (comprobante)
    public ?int $informandoMatriculaId = null;

    public ?int $pagoMonto = null;

    public ?string $pagoBanco = null;

    public ?string $pagoReferencia = null;

    public $comprobante = null;

    public bool $mostrarInformar = false;

    public function abrirInformar(int $matriculaId): void
    {
        $this->reset('pagoMonto', 'pagoBanco', 'pagoReferencia', 'comprobante');
        $this->informandoMatriculaId = $matriculaId;
        $this->resetErrorBag();
        $this->mostrarInformar = true;
    }

    public function informarPago(ServicioPagos $servicio): void
    {
        $datos = $this->validate([
            'informandoMatriculaId' => ['required'],
            'pagoMonto' => ['required', 'integer', 'min:1'],
            'pagoBanco' => ['nullable', 'string', 'max:100'],
            'pagoReferencia' => ['nullable', 'string', 'max:100'],
            'comprobante' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        // Solo matrículas que el apoderado tutela (misma regla que la vista).
        $matricula = Matricula::visiblePara(Auth::user())
            ->findOrFail($datos['informandoMatriculaId']);

        $ruta = $this->comprobante->store('comprobantes');

        $servicio->registrarPago($matricula, $datos['pagoMonto'], now(), [
            'estado' => EstadoPago::PorVerificar,
            'banco' => $datos['pagoBanco'] ?? null,
            'referencia' => $datos['pagoReferencia'] ?? null,
            'comprobante_archivo' => $ruta,
            'pagado_por_persona_id' => Auth::user()->persona_id,
        ]);

        Flux::toast(variant: 'success', text: 'Pago informado. Queda por verificar.');
        $this->mostrarInformar = false;
    }

    public function render(ServicioPagos $servicio)
    {
        $matriculas = Matricula::visiblePara(Auth::user())
            ->with(['persona.grado', 'sede'])
            ->get();

        // Estado de cuenta por matrícula: badge de morosidad + deuda total.
        $estados = $matriculas->mapWithKeys(function (Matricula $m) use ($servicio) {
            $pendientes = $servicio->cargosPendientes($m);

            return [$m->id => [
                'moroso' => $servicio->estaMoroso($m),
                'deuda' => (int) $pendientes->sum('monto'),
                'cargos' => $pendientes->count(),
            ]];
        });

        return view('livewire.apoderado.mis-estudiantes', [
            'matriculas' => $matriculas,
            'estados' => $estados,
        ]);
    }
}
