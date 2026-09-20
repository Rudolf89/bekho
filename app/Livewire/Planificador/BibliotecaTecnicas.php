<?php

namespace App\Livewire\Planificador;

use App\Enums\CategoriaTecnica;
use App\Enums\ModalidadTecnica;
use App\Models\Tecnica;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Biblioteca de técnicas del currículo ATA: patadas, formas, manos, tricks y
 * armas, con filtros por categoría y modalidad. Contenido de referencia
 * compartido (transversal).
 */
#[Title('Biblioteca de técnicas')]
class BibliotecaTecnicas extends Component
{
    #[Url]
    public string $categoria = '';

    #[Url]
    public string $modalidad = '';

    public string $buscar = '';

    public function render()
    {
        $tecnicas = Tecnica::query()
            ->when($this->categoria !== '', fn ($q) => $q->where('categoria', $this->categoria))
            ->when($this->modalidad !== '', fn ($q) => $q->where('modalidad', $this->modalidad))
            ->when($this->buscar !== '', fn ($q) => $q->where(fn ($s) => $s
                ->where('nombre', 'ilike', "%{$this->buscar}%")
                ->orWhere('descripcion', 'ilike', "%{$this->buscar}%")
                ->orWhere('cinturon', 'ilike', "%{$this->buscar}%")))
            ->with(['pasos', 'grados'])
            ->ordenadas()
            ->get();

        return view('livewire.planificador.biblioteca-tecnicas', [
            'grupos' => $tecnicas->groupBy(fn (Tecnica $t) => $t->categoria->value),
            'categorias' => CategoriaTecnica::cases(),
            'modalidades' => ModalidadTecnica::cases(),
            'total' => $tecnicas->count(),
        ]);
    }
}
