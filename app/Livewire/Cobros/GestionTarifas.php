<?php

namespace App\Livewire\Cobros;

use App\Livewire\Concerns\SoloLectura;
use App\Models\Sede;
use App\Models\TarifaSede;
use App\Models\TipoCargo;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Administración de tarifas POR SEDE (matrícula, mensualidad, …). La dirección de
 * grupo ve todas sus sedes; un direccion-sede solo la suya (User::sedesRestringidas).
 * Cada sede define sus propios valores; sin tarifas no se pueden generar sus cargos.
 */
#[Title('Tarifas')]
class GestionTarifas extends Component
{
    use SoloLectura;

    public ?int $sedeActivaId = null;

    public string $tarifaTipoId = '';

    public ?int $tarifaCantidad = 1;

    public ?int $tarifaMonto = null;

    public ?string $tarifaVigenteDesde = null;

    public bool $mostrarModal = false;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'tarifaTipoId' => ['required', Rule::exists('tipos_cargo', 'id')],
            'tarifaCantidad' => ['required', 'integer', 'min:1', 'max:20'],
            'tarifaMonto' => ['required', 'integer', 'min:0'],
            'tarifaVigenteDesde' => ['nullable', 'date'],
        ];
    }

    public function abrirNueva(int $sedeId): void
    {
        $this->bloqueaSiSoloLectura();
        abort_unless($this->sedesVisibles()->contains('id', $sedeId), 403);

        $this->reset('tarifaTipoId', 'tarifaCantidad', 'tarifaMonto', 'tarifaVigenteDesde');
        $this->tarifaCantidad = 1;
        $this->sedeActivaId = $sedeId;
        $this->resetErrorBag();
        $this->mostrarModal = true;
    }

    public function guardarTarifa(): void
    {
        $this->bloqueaSiSoloLectura();
        abort_unless($this->sedesVisibles()->contains('id', $this->sedeActivaId), 403);

        $datos = $this->validate();

        TarifaSede::updateOrCreate(
            [
                'sede_id' => $this->sedeActivaId,
                'tipo_cargo_id' => $datos['tarifaTipoId'],
                'cantidad_alumnos' => $datos['tarifaCantidad'],
                'vigente_desde' => $datos['tarifaVigenteDesde'] ?: null,
            ],
            ['monto_por_alumno' => $datos['tarifaMonto']],
        );

        Flux::toast(variant: 'success', text: 'Tarifa guardada.');
        $this->mostrarModal = false;
    }

    public function eliminarTarifa(int $id): void
    {
        $this->bloqueaSiSoloLectura();

        $tarifa = TarifaSede::find($id);
        if ($tarifa && $this->sedesVisibles()->contains('id', $tarifa->sede_id)) {
            $tarifa->delete();
        }
    }

    /**
     * Sedes que el usuario puede administrar: las de su grupo, acotadas a su(s)
     * sede(s) si tiene un rol por sede.
     *
     * @return Collection<int, Sede>
     */
    protected function sedesVisibles(): Collection
    {
        $restringidas = Auth::user()?->sedesRestringidas();

        return Sede::query()
            ->when($restringidas !== null, fn ($q) => $q->whereIn('id', $restringidas))
            ->orderBy('nombre')
            ->get();
    }

    public function render(): View
    {
        $sedes = $this->sedesVisibles()->load('tarifas.tipoCargo');

        return view('livewire.cobros.gestion-tarifas', [
            'sedes' => $sedes,
            'tipos' => TipoCargo::orderBy('orden')->get(),
        ]);
    }
}
