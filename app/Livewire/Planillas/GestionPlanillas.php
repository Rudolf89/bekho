<?php

namespace App\Livewire\Planillas;

use App\Enums\EstadoPlanilla;
use App\Livewire\Concerns\SoloLectura;
use App\Models\ColumnaPlanilla;
use App\Models\Federacion;
use App\Models\Planilla;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Catálogo de planillas imprimibles del programa: los formularios en papel que
 * se llenan en la cancha. Cualquiera con "gestionar planificaciones" las ve e
 * imprime; el catálogo es de la federación, así que crearlas y editarlas exige
 * "gestionar programas" (dirección/federación).
 *
 * Cada planilla declara sus columnas: con ellas se imprime la grilla en blanco.
 */
#[Title('Planillas')]
class GestionPlanillas extends Component
{
    use SoloLectura;

    public bool $mostrarModal = false;

    public ?int $editandoId = null;

    public string $nombre = '';

    public string $uso = '';

    public string $descripcion = '';

    public string $estado = '';

    public int $version = 1;

    public int $filas = 20;

    /**
     * Columnas en edición, en orden de impresión.
     *
     * @var list<string>
     */
    public array $columnas = [];

    public function mount(): void
    {
        $this->estado = EstadoPlanilla::Borrador->value;
    }

    /**
     * ¿Puede el usuario tocar el catálogo? Verlo e imprimirlo es otra cosa.
     */
    public function puedeEditar(): bool
    {
        return Auth::user()->can('gestionar programas') && ! Auth::user()->esSoloLectura();
    }

    public function nueva(): void
    {
        abort_unless($this->puedeEditar(), 403);

        $this->reset('editandoId', 'nombre', 'uso', 'descripcion', 'version', 'filas', 'columnas');
        $this->estado = EstadoPlanilla::Borrador->value;
        $this->columnas = [''];
        $this->resetErrorBag();
        $this->mostrarModal = true;
    }

    public function editar(Planilla $planilla): void
    {
        abort_unless($this->puedeEditar(), 403);

        $this->editandoId = $planilla->id;
        $this->nombre = $planilla->nombre;
        $this->uso = (string) $planilla->uso;
        $this->descripcion = (string) $planilla->descripcion;
        $this->estado = $planilla->estado->value;
        $this->version = $planilla->version;
        $this->filas = $planilla->filas;
        $this->columnas = array_values(
            $planilla->columnas->map(fn (ColumnaPlanilla $c) => $c->titulo)->all()
        );

        if ($this->columnas === []) {
            $this->columnas = [''];
        }

        $this->resetErrorBag();
        $this->mostrarModal = true;
    }

    public function agregarColumna(): void
    {
        $this->columnas[] = '';
    }

    public function quitarColumna(int $i): void
    {
        array_splice($this->columnas, $i, 1);
    }

    public function guardar(): void
    {
        abort_unless($this->puedeEditar(), 403);
        $this->bloqueaSiSoloLectura();

        $datos = $this->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'uso' => ['nullable', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'estado' => ['required', 'in:'.implode(',', array_column(EstadoPlanilla::cases(), 'value'))],
            'version' => ['required', 'integer', 'min:1', 'max:99'],
            'filas' => ['required', 'integer', 'min:1', 'max:60'],
        ], [], ['nombre' => 'nombre', 'version' => 'versión', 'filas' => 'filas en blanco']);

        // Las columnas vacías se descartan: una planilla puede quedar sin columnas
        // (todavía no se define su grilla) y entonces no se puede imprimir.
        $columnas = array_values(array_filter(array_map('trim', $this->columnas), fn (string $t) => $t !== ''));

        $federacionId = Federacion::query()->orderBy('id')->value('id');

        $planilla = Planilla::updateOrCreate(
            ['id' => $this->editandoId],
            [...$datos, 'federacion_id' => $federacionId, 'activo' => true],
        );

        $planilla->columnas()->delete();
        foreach ($columnas as $orden => $titulo) {
            $planilla->columnas()->create(['titulo' => $titulo, 'orden' => $orden + 1]);
        }

        $this->mostrarModal = false;
        Flux::toast(variant: 'success', text: 'Planilla guardada.');
    }

    public function eliminar(Planilla $planilla): void
    {
        abort_unless($this->puedeEditar(), 403);
        $this->bloqueaSiSoloLectura();

        $planilla->delete();
        Flux::toast(variant: 'success', text: 'Planilla eliminada.');
    }

    public function render(): View
    {
        return view('livewire.planillas.gestion-planillas', [
            'planillas' => Planilla::with('columnas')->where('activo', true)->ordenadas()->get(),
            'estados' => EstadoPlanilla::cases(),
            'puedeEditar' => $this->puedeEditar(),
        ]);
    }
}
