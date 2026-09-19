<?php

namespace App\Services;

use App\Enums\EstadoCargo;
use App\Enums\EstadoMatricula;
use App\Enums\EstadoPago;
use App\Models\Cargo;
use App\Models\Matricula;
use App\Models\Pago;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Lógica de dominio de pagos y morosidad, sobre CARGOS (rediseño Fase 5b).
 *
 * La deuda vive en los cargos: una matrícula activa (no suspendida) está morosa
 * si tiene un cargo pendiente cuyo período ya llegó. Un pago es un abono con
 * verificación que se aplica a uno o varios cargos (pivote pago_cargo); solo los
 * pagos verificados cubren un cargo y lo dejan "pagado".
 */
class ServicioPagos
{
    /**
     * Primer día del mes de un período (por defecto, el mes actual).
     */
    public function periodo(?CarbonInterface $fecha = null): CarbonInterface
    {
        return ($fecha ?? now())->copy()->startOfMonth();
    }

    /**
     * Cargos pendientes de la matrícula con período vencido (≤ el dado o sin
     * período), ordenados del más antiguo al más nuevo.
     *
     * @return Collection<int, Cargo>
     */
    public function cargosPendientes(Matricula $matricula, ?CarbonInterface $periodo = null): Collection
    {
        $periodo = $this->periodo($periodo);

        return Cargo::withoutGlobalScopes()
            ->where('matricula_id', $matricula->id)
            ->pendientes()
            ->where(fn ($q) => $q->whereNull('periodo')->orWhereDate('periodo', '<=', $periodo))
            ->orderByRaw('periodo is null')
            ->orderBy('periodo')
            ->orderBy('id')
            ->get();
    }

    /**
     * Indica si la matrícula está al día (sin cargos pendientes vencidos).
     */
    public function estaAlDia(Matricula $matricula, ?CarbonInterface $periodo = null): bool
    {
        return $this->cargosPendientes($matricula, $periodo)->isEmpty();
    }

    /**
     * Indica si la matrícula (activa) está morosa en el período dado. Una
     * matrícula suspendida en ese período no genera cargo, así que no es morosa.
     */
    public function estaMoroso(Matricula $matricula, ?CarbonInterface $periodo = null): bool
    {
        $periodo = $this->periodo($periodo);

        return $matricula->estado === EstadoMatricula::Activa
            && ! $matricula->estaSuspendidaEn($periodo)
            && $this->cargosPendientes($matricula, $periodo)->isNotEmpty();
    }

    /**
     * Matrículas activas morosas del período (respeta el scope por grupo): tienen
     * un cargo pendiente vencido y no están suspendidas en el período.
     *
     * @return Collection<int, Matricula>
     */
    public function morosos(?CarbonInterface $periodo = null): Collection
    {
        $periodo = $this->periodo($periodo);

        return Matricula::activas()
            ->whereHas('cargos', fn ($q) => $q
                ->where('estado', EstadoCargo::Pendiente->value)
                ->where(fn ($sub) => $sub->whereNull('periodo')->orWhereDate('periodo', '<=', $periodo)))
            ->whereDoesntHave('suspensiones', fn ($q) => $q->cubrePeriodo($periodo))
            ->with('persona')
            ->get()
            ->sortBy(fn (Matricula $m) => $m->persona?->nombreCompleto())
            ->values();
    }

    /**
     * Registra un pago de la matrícula y lo aplica a sus cargos pendientes. Por
     * defecto el pago nace verificado (registro manual de dirección/recepción);
     * pasar estado PorVerificar para un comprobante subido por el apoderado.
     *
     * @param  array<string, mixed>  $opts  estado, banco, referencia, comprobante_archivo, pagado_por_persona_id, cargos
     */
    public function registrarPago(Matricula $matricula, int $monto, CarbonInterface $fechaPago, array $opts = []): Pago
    {
        $estado = $opts['estado'] ?? EstadoPago::Verificado;

        $pago = Pago::create([
            'grupo_id' => $matricula->grupo_id,
            'pagado_por_persona_id' => $opts['pagado_por_persona_id'] ?? $matricula->persona_id,
            'monto' => $monto,
            'fecha_pago' => $fechaPago,
            'banco' => $opts['banco'] ?? null,
            'referencia' => $opts['referencia'] ?? null,
            'comprobante_archivo' => $opts['comprobante_archivo'] ?? null,
            'estado' => $estado->value,
            'registrado_por' => Auth::id(),
            'verificado_por_user_id' => $estado === EstadoPago::Verificado ? Auth::id() : null,
            'verificado_at' => $estado === EstadoPago::Verificado ? now() : null,
        ]);

        $cargos = $opts['cargos'] ?? $this->cargosPendientes($matricula);
        $this->aplicar($pago, $cargos);

        if ($estado === EstadoPago::Verificado) {
            $this->marcarCubiertos($cargos);
        }

        return $pago;
    }

    /**
     * Verifica un pago: lo confirma y marca como pagados los cargos que cubre.
     */
    public function verificar(Pago $pago, User $verificador): void
    {
        $pago->update([
            'estado' => EstadoPago::Verificado->value,
            'verificado_por_user_id' => $verificador->id,
            'verificado_at' => now(),
        ]);

        $this->marcarCubiertos($pago->cargos()->get());
    }

    /**
     * Anula un pago (nunca se borra): revierte a pendiente los cargos que dejen
     * de estar cubiertos por pagos verificados.
     */
    public function anular(Pago $pago, User $usuario, ?string $motivo = null): void
    {
        $cargos = $pago->cargos()->get();

        $pago->update([
            'estado' => EstadoPago::Anulado->value,
            'motivo_anulacion' => $motivo,
        ]);

        foreach ($cargos as $cargo) {
            if ($cargo->estado === EstadoCargo::Pagado && ! $cargo->estaCubierto()) {
                $cargo->update(['estado' => EstadoCargo::Pendiente->value]);
            }
        }
    }

    /**
     * Aplica el monto del pago a los cargos (del más antiguo al más nuevo),
     * registrando el monto aplicado a cada uno en el pivote.
     *
     * @param  Collection<int, Cargo>  $cargos
     */
    protected function aplicar(Pago $pago, Collection $cargos): void
    {
        $restante = $pago->monto;

        foreach ($cargos as $cargo) {
            if ($restante <= 0) {
                break;
            }

            $saldo = $this->saldoCargo($cargo);
            if ($saldo <= 0) {
                continue;
            }

            $aplicado = min($restante, $saldo);
            $pago->cargos()->syncWithoutDetaching([$cargo->id => ['monto_aplicado' => $aplicado]]);
            $restante -= $aplicado;
        }
    }

    /**
     * Marca como pagados los cargos totalmente cubiertos por pagos verificados.
     *
     * @param  Collection<int, Cargo>  $cargos
     */
    protected function marcarCubiertos(Collection $cargos): void
    {
        foreach ($cargos as $cargo) {
            if ($cargo->estado !== EstadoCargo::Pagado && $cargo->estaCubierto()) {
                $cargo->update(['estado' => EstadoCargo::Pagado->value]);
            }
        }
    }

    /**
     * Saldo de un cargo: su monto menos lo ya aplicado por pagos no anulados.
     */
    protected function saldoCargo(Cargo $cargo): int
    {
        $aplicado = (int) $cargo->pagos()
            ->where('estado', '!=', EstadoPago::Anulado->value)
            ->sum('pago_cargo.monto_aplicado');

        return max(0, $cargo->monto - $aplicado);
    }
}
