<?php

namespace App\Livewire\Pagos;

use App\Enums\TipoPago;
use App\Models\ConfiguracionPago;
use App\Models\Estudiante;
use App\Models\Pago;
use App\Services\ServicioPagos;
use App\Support\Tenancy\Academia;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Pagos')]
class GestionPagos extends Component
{
    // Formulario de registro de pago
    public ?int $pagoEstudianteId = null;

    public string $pagoTipo = 'mensualidad';

    public ?int $pagoMonto = null;

    public string $pagoFechaPago = '';

    public ?string $pagoMedio = null;

    public bool $mostrarModal = false;

    // Configuración de pagos (por academia)
    public ?int $valor_mensualidad = null;

    public ?int $valor_matricula = null;

    public ?int $dia_vencimiento = null;

    public int $descuento_hermanos_pct = 20;

    public bool $mostrarConfig = false;

    public function mount(): void
    {
        $this->pagoFechaPago = now()->format('Y-m-d');
        $this->cargarConfig();
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
     * Configuración de pagos de la academia activa (se crea si no existe).
     *
     * El super-admin no tiene academia activa (ve todas); en ese caso se usa su
     * academia o, en su defecto, la primera, para no insertar academia_id nulo.
     */
    protected function config(): ConfiguracionPago
    {
        $academiaId = Academia::id()
            ?? Auth::user()?->academia_id
            ?? \App\Models\Academia::query()->orderBy('id')->value('id');

        // Si no hay ninguna academia, devuelve una configuración transitoria.
        if (! $academiaId) {
            return new ConfiguracionPago(['descuento_hermanos_pct' => 20]);
        }

        return ConfiguracionPago::firstOrCreate(
            ['academia_id' => $academiaId],
            ['descuento_hermanos_pct' => 20],
        );
    }

    public function abrirRegistro(?int $estudianteId = null): void
    {
        $this->reset('pagoTipo', 'pagoMonto', 'pagoMedio');
        $this->pagoEstudianteId = $estudianteId;
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
        return view('livewire.pagos.gestion-pagos', [
            'morosos' => $servicio->morosos(),
            'periodo' => $servicio->periodo()->translatedFormat('F Y'),
            'pagosRecientes' => Pago::with('estudiante')->latest('fecha_pago')->latest('id')->limit(15)->get(),
            'estudiantes' => Estudiante::activos()->orderBy('nombre')->get(),
            'tipos' => TipoPago::cases(),
        ]);
    }
}
