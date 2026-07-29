<?php

namespace App\Livewire\Legacy;

use App\Enums\EstadoLegacy;
use App\Models\InscripcionLegacy;
use App\Models\NivelLegacy;
use App\Models\RequisitoLegacy;
use App\Models\User;
use App\Support\Tenancy\Academia as Tenant;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Programa Legacy: track de formación de instructores. El instructor/dirección
 * inscribe usuarios, registra horas y verifica requisitos; el licenciatario
 * aprueba el ascenso cuando se cumplen las 100 h y todos los requisitos.
 */
#[Title('Programa Legacy')]
class PanelLegacy extends Component
{
    // Nueva inscripción.
    public string $nuevoUserId = '';

    public string $nuevoNivelId = '';

    // Detalle abierto.
    #[Url]
    public ?int $inscripcionId = null;

    // Alta de horas.
    public string $horaFecha = '';

    public string $horaCantidad = '';

    public string $horaDescripcion = '';

    public function crearInscripcion(): void
    {
        abort_unless(Auth::user()->can('gestionar legacy'), 403);

        $this->validate([
            'nuevoUserId' => ['required', 'exists:users,id'],
            'nuevoNivelId' => ['required', 'exists:niveles_legacy,id'],
        ], [], ['nuevoUserId' => 'usuario', 'nuevoNivelId' => 'nivel']);

        $inscripcion = InscripcionLegacy::firstOrCreate(
            ['user_id' => $this->nuevoUserId, 'nivel_legacy_id' => $this->nuevoNivelId],
            ['estado' => EstadoLegacy::EnCurso->value, 'fecha_inicio' => now()->toDateString()],
        );

        $this->reset('nuevoUserId', 'nuevoNivelId');
        $this->inscripcionId = $inscripcion->id;
    }

    public function agregarHora(): void
    {
        abort_unless(Auth::user()->can('gestionar legacy'), 403);

        $inscripcion = $this->inscripcion();
        if (! $inscripcion) {
            return;
        }

        $this->validate([
            'horaFecha' => ['required', 'date'],
            'horaCantidad' => ['required', 'numeric', 'min:0.5', 'max:24'],
        ], [], ['horaFecha' => 'fecha', 'horaCantidad' => 'horas']);

        $inscripcion->horas()->create([
            'fecha' => $this->horaFecha,
            'horas' => $this->horaCantidad,
            'descripcion' => $this->horaDescripcion ?: null,
            'verificado_por' => Auth::id(),
        ]);

        $this->reset('horaFecha', 'horaCantidad', 'horaDescripcion');
    }

    public function eliminarHora(int $horaId): void
    {
        abort_unless(Auth::user()->can('gestionar legacy'), 403);

        $this->inscripcion()?->horas()->whereKey($horaId)->delete();
    }

    public function alternarRequisito(int $requisitoId): void
    {
        abort_unless(Auth::user()->can('gestionar legacy'), 403);

        $inscripcion = $this->inscripcion();
        $requisito = RequisitoLegacy::find($requisitoId);
        if (! $inscripcion || ! $requisito || $requisito->esAutomatico()) {
            return;
        }

        if ($inscripcion->requisitosCumplidos()->where('requisito_legacy_id', $requisitoId)->exists()) {
            $inscripcion->requisitosCumplidos()->detach($requisitoId);
        } else {
            $inscripcion->requisitosCumplidos()->attach($requisitoId, [
                'verificado_por' => Auth::id(),
                'verificado_at' => now(),
            ]);
        }
    }

    public function aprobar(): void
    {
        abort_unless(Auth::user()->can('aprobar legacy'), 403);

        $inscripcion = $this->inscripcion();
        if (! $inscripcion || ! $inscripcion->puedeAprobar()) {
            Flux::toast(variant: 'warning', text: 'Aún no cumple las 100 h y todos los requisitos.');

            return;
        }

        $inscripcion->update([
            'estado' => EstadoLegacy::Aprobado->value,
            'fecha_aprobacion' => now()->toDateString(),
            'aprobado_por' => Auth::id(),
        ]);

        Flux::toast(variant: 'success', text: 'Ascenso aprobado.');
    }

    private function inscripcion(): ?InscripcionLegacy
    {
        return $this->inscripcionId
            ? InscripcionLegacy::with(['user', 'nivel.requisitos.cuestionario', 'horas', 'requisitosCumplidos'])->find($this->inscripcionId)
            : null;
    }

    public function render()
    {
        return view('livewire.legacy.panel-legacy', [
            'inscripciones' => InscripcionLegacy::with(['user', 'nivel'])->latest()->get(),
            'inscripcion' => $this->inscripcion(),
            'usuarios' => User::query()
                ->when(Tenant::id(), fn ($q, $id) => $q->where('academia_id', $id))
                ->orderBy('name')->get(),
            'niveles' => NivelLegacy::ordenados()->get(),
            'puedeAprobar' => Auth::user()->can('aprobar legacy'),
        ]);
    }
}
