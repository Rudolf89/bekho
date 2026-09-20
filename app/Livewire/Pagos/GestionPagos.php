<?php

namespace App\Livewire\Pagos;

use App\Enums\EstadoPago;
use App\Livewire\Concerns\ConTabla;
use App\Models\ConfiguracionPago;
use App\Models\Matricula;
use App\Models\Pago;
use App\Services\ServicioPagos;
use App\Support\Tenancy\Grupo;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Pagos')]
class GestionPagos extends Component
{
    use ConTabla, WithPagination;

    /** Filtro por estado del pago (por verificar/verificado/anulado); '' = todos. */
    public string $filtroEstado = '';

    // Formulario de registro de pago
    public ?string $pagoMatriculaId = '';

    public ?int $pagoMonto = null;

    public string $pagoFechaPago = '';

    public ?string $pagoBanco = null;

    public ?string $pagoReferencia = null;

    public bool $mostrarModal = false;

    // Anulación
    public ?int $anulandoId = null;

    public string $motivoAnulacion = '';

    public bool $mostrarAnular = false;

    // Configuración de pagos (por grupo)
    public ?int $valor_mensualidad = null;

    public ?int $valor_matricula = null;

    public ?int $dia_vencimiento = null;

    public int $descuento_hermanos_pct = 20;

    public bool $mostrarConfig = false;

    public function mount(): void
    {
        $this->pagoFechaPago = now()->format('Y-m-d');
        if ($this->ordenCampo === '') {
            $this->ordenCampo = 'fecha_pago';
            $this->ordenDir = 'desc';
        }
        $this->cargarConfig();
    }

    public function updatedFiltroEstado(): void
    {
        $this->resetPage();
    }

    protected function cargarConfig(): void
    {
        $config = $this->config();
        $this->valor_mensualidad = $config->valor_mensualidad;
        $this->valor_matricula = $config->valor_matricula;
        $this->dia_vencimiento = $config->dia_vencimiento;
        $this->descuento_hermanos_pct = $config->descuento_hermanos_pct;
    }

    /**
     * Configuración de pagos del grupo activo (se crea si no existe).
     */
    protected function config(): ConfiguracionPago
    {
        $grupoId = Grupo::id()
            ?? Auth::user()?->grupo_id
            ?? \App\Models\Grupo::query()->orderBy('id')->value('id');

        if (! $grupoId) {
            return new ConfiguracionPago(['descuento_hermanos_pct' => 20]);
        }

        return ConfiguracionPago::firstOrCreate(
            ['grupo_id' => $grupoId],
            ['descuento_hermanos_pct' => 20],
        );
    }

    public function abrirRegistro(?int $matriculaId = null): void
    {
        $this->reset('pagoMonto', 'pagoBanco', 'pagoReferencia');
        $this->pagoMatriculaId = (string) ($matriculaId ?? '');
        $this->pagoFechaPago = now()->format('Y-m-d');
        $this->resetErrorBag();
        $this->mostrarModal = true;
    }

    public function registrarPago(ServicioPagos $servicio): void
    {
        $datos = $this->validate([
            'pagoMatriculaId' => ['required', Rule::exists('matriculas', 'id')],
            'pagoMonto' => ['required', 'integer', 'min:1'],
            'pagoFechaPago' => ['required', 'date'],
            'pagoBanco' => ['nullable', 'string', 'max:100'],
            'pagoReferencia' => ['nullable', 'string', 'max:100'],
        ]);

        $matricula = Matricula::findOrFail($datos['pagoMatriculaId']);

        // Registro manual de dirección/recepción: nace verificado y se aplica a
        // los cargos pendientes de la matrícula.
        $servicio->registrarPago(
            $matricula,
            $datos['pagoMonto'],
            Carbon::parse($datos['pagoFechaPago']),
            [
                'estado' => EstadoPago::Verificado,
                'banco' => $datos['pagoBanco'] ?? null,
                'referencia' => $datos['pagoReferencia'] ?? null,
            ],
        );

        Flux::toast(variant: 'success', text: 'Pago registrado.');
        $this->mostrarModal = false;
    }

    public function verificar(int $pagoId, ServicioPagos $servicio): void
    {
        $pago = Pago::findOrFail($pagoId);
        $servicio->verificar($pago, Auth::user());

        Flux::toast(variant: 'success', text: 'Pago verificado.');
    }

    public function abrirAnular(int $pagoId): void
    {
        $this->anulandoId = $pagoId;
        $this->motivoAnulacion = '';
        $this->resetErrorBag();
        $this->mostrarAnular = true;
    }

    public function anular(ServicioPagos $servicio): void
    {
        $this->validate(['motivoAnulacion' => ['required', 'string', 'max:255']]);

        $pago = Pago::findOrFail($this->anulandoId);
        $servicio->anular($pago, Auth::user(), $this->motivoAnulacion);

        Flux::toast(variant: 'success', text: 'Pago anulado.');
        $this->mostrarAnular = false;
    }

    public function guardarConfig(): void
    {
        $datos = $this->validate([
            'valor_mensualidad' => ['nullable', 'integer', 'min:0'],
            'valor_matricula' => ['nullable', 'integer', 'min:0'],
            'dia_vencimiento' => ['nullable', 'integer', 'min:1', 'max:31'],
            'descuento_hermanos_pct' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $this->config()->update($datos);

        Flux::toast(variant: 'success', text: 'Configuración de pagos actualizada.');
        $this->mostrarConfig = false;
    }

    public function render(ServicioPagos $servicio)
    {
        // Consulta base filtrable: búsqueda por quien pagó / referencia + estado.
        $base = $this->aplicarBusqueda(
            Pago::with('pagadoPor'),
            ['pagadoPor.nombres', 'pagadoPor.apellido_paterno', 'referencia'],
        )->when($this->filtroEstado !== '', fn ($q) => $q->where('estado', $this->filtroEstado));

        // El total recaudado suma solo los pagos verificados del filtro.
        $totalPagos = (clone $base)->count();
        $sumaPagos = (clone $base)->where('estado', EstadoPago::Verificado->value)->sum('monto');

        $pagos = $this->aplicarOrden($base, ['fecha_pago', 'monto'], 'fecha_pago')
            ->latest('id')
            ->paginate(15);

        return view('livewire.pagos.gestion-pagos', [
            'morosos' => $servicio->morosos(),
            'periodo' => $servicio->periodo()->translatedFormat('F Y'),
            'pagos' => $pagos,
            'totalPagos' => $totalPagos,
            'sumaPagos' => $sumaPagos,
            'matriculas' => Matricula::activas()->with('persona')->get()
                ->sortBy(fn (Matricula $m) => $m->persona?->nombreCompleto())->values(),
            'estados' => EstadoPago::cases(),
        ]);
    }
}
