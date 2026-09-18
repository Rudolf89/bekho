<?php

namespace App\Livewire\Pagos;

use App\Enums\TipoPago;
use App\Livewire\Concerns\ConTabla;
use App\Models\ConfiguracionPago;
use App\Models\Estudiante;
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

    /** Filtro por tipo de pago (mensualidad/matrícula/…); '' = todos. */
    public string $filtroTipo = '';

    // Formulario de registro de pago
    public ?string $pagoEstudianteId = '';

    public string $pagoTipo = 'mensualidad';

    public ?int $pagoMonto = null;

    public string $pagoFechaPago = '';

    public ?string $pagoMedio = null;

    public bool $mostrarModal = false;

    // Configuración de pagos (por grupo)
    public ?int $valor_mensualidad = null;

    public ?int $valor_matricula = null;

    public ?int $dia_vencimiento = null;

    public int $descuento_hermanos_pct = 20;

    public bool $mostrarConfig = false;

    public function mount(): void
    {
        $this->pagoFechaPago = now()->format('Y-m-d');
        // Por defecto la tabla se ordena por fecha de pago, más reciente primero.
        if ($this->ordenCampo === '') {
            $this->ordenCampo = 'fecha_pago';
            $this->ordenDir = 'desc';
        }
        $this->cargarConfig();
    }

    public function updatedFiltroTipo(): void
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
     *
     * El admin-plataforma no tiene grupo activo (ve todas); en ese caso se usa su
     * grupo o, en su defecto, la primera, para no insertar grupo_id nulo.
     */
    protected function config(): ConfiguracionPago
    {
        $grupoId = Grupo::id()
            ?? Auth::user()?->grupo_id
            ?? \App\Models\Grupo::query()->orderBy('id')->value('id');

        // Si no hay ningun grupo, devuelve una configuración transitoria.
        if (! $grupoId) {
            return new ConfiguracionPago(['descuento_hermanos_pct' => 20]);
        }

        return ConfiguracionPago::firstOrCreate(
            ['grupo_id' => $grupoId],
            ['descuento_hermanos_pct' => 20],
        );
    }

    public function abrirRegistro(?int $estudianteId = null): void
    {
        $this->reset('pagoTipo', 'pagoMonto', 'pagoMedio');
        $this->pagoEstudianteId = (string) ($estudianteId ?? '');
        $this->pagoFechaPago = now()->format('Y-m-d');
        $this->resetErrorBag();
        $this->mostrarModal = true;
    }

    public function registrarPago(ServicioPagos $servicio): void
    {
        $datos = $this->validate([
            'pagoEstudianteId' => ['required', Rule::exists('estudiantes', 'id')],
            'pagoTipo' => ['required', Rule::enum(TipoPago::class)],
            'pagoMonto' => ['required', 'integer', 'min:1'],
            'pagoFechaPago' => ['required', 'date'],
            'pagoMedio' => ['nullable', 'string', 'max:100'],
        ]);

        $estudiante = Estudiante::findOrFail($datos['pagoEstudianteId']);

        $servicio->registrarPago(
            $estudiante,
            TipoPago::from($datos['pagoTipo']),
            $datos['pagoMonto'],
            Carbon::parse($datos['pagoFechaPago']),
            medio: $datos['pagoMedio'] ?? null,
        );

        Flux::toast(variant: 'success', text: 'Pago registrado.');
        $this->mostrarModal = false;
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
        // Consulta base filtrable: búsqueda por alumno/medio + filtro por tipo.
        $base = $this->aplicarBusqueda(Pago::with('estudiante'), ['estudiante.nombre', 'medio'])
            ->when($this->filtroTipo !== '', fn ($q) => $q->where('tipo', $this->filtroTipo));

        // El resumen suma TODOS los pagos que calzan con el filtro (no solo la página).
        $totalPagos = (clone $base)->count();
        $sumaPagos = (clone $base)->sum('monto');

        $pagos = $this->aplicarOrden($base, ['fecha_pago', 'monto'], 'fecha_pago')
            ->latest('id')
            ->paginate(15);

        return view('livewire.pagos.gestion-pagos', [
            'morosos' => $servicio->morosos(),
            'periodo' => $servicio->periodo()->translatedFormat('F Y'),
            'pagos' => $pagos,
            'totalPagos' => $totalPagos,
            'sumaPagos' => $sumaPagos,
            'estudiantes' => Estudiante::activos()->orderBy('nombre')->get(),
            'tipos' => TipoPago::cases(),
        ]);
    }
}
