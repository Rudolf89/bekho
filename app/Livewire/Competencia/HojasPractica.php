<?php

namespace App\Livewire\Competencia;

use App\Support\Competencia\HojasPractica as Datos;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Hojas en blanco de la planilla de competencia para PRACTICAR el llenado a
 * mano: las usan los aspirantes a planillero, los jueces y los alumnos Legacy.
 *
 * Va con "ver programas" (no con "gestionar competencia") porque practicar no
 * es operar un torneo. Los alumnos Legacy menores no tienen cuenta: su
 * instructor les imprime la hoja.
 */
#[Title('Hojas para practicar')]
class HojasPractica extends Component
{
    public function render(Datos $datos): View
    {
        return view('livewire.competencia.hojas-practica', [
            'pruebas' => $datos->pruebasConCriterios(),
        ]);
    }
}
