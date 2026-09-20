<?php

namespace App\Livewire\Planificador;

use App\Models\Forma;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Formas Songahm (poomsae) del currículo ATA: cada forma con su secuencia de
 * movimientos (postura y sección de altura). Contenido del Manual ATA Legacy
 * (verificado). Las formas de cinturón negro que el manual nombra pero no detalla
 * aparecen sin secuencia y marcadas como no verificadas.
 */
#[Title('Formas')]
class Formas extends Component
{
    public string $buscar = '';

    public function render()
    {
        $formas = Forma::query()
            ->when($this->buscar !== '', fn ($q) => $q->whereRaw(
                'LOWER(nombre) LIKE ?', ['%'.mb_strtolower(trim($this->buscar)).'%']
            ))
            ->with(['pasos.posicion', 'grado'])
            ->ordenadas()
            ->get();

        return view('livewire.planificador.formas', [
            'formas' => $formas,
            'total' => $formas->count(),
        ]);
    }
}
