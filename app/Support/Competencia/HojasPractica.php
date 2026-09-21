<?php

namespace App\Support\Competencia;

use App\Models\CategoriaCompetencia;
use App\Models\GrupoEdad;
use App\Models\Prueba;
use App\Models\TablaLibre;
use Illuminate\Support\Collection;

/**
 * Datos de las hojas en blanco con que se PRACTICA el llenado de la planilla de
 * competencia (aspirantes a planillero, jueces y alumnos Legacy).
 *
 * Todo sale de los catálogos sembrados desde la planilla oficial, para que la
 * hoja impresa calce con la planilla digital: nada se escribe a mano aquí.
 */
class HojasPractica
{
    /** Procedencia que se imprime al pie de cada hoja. */
    public const FUENTE = 'Planilla de competencia oficial BEKHO';

    /** Competidores que entran en la planilla de fórmula/armas y en la llave. */
    public const COMPETIDORES = 16;

    /**
     * Grupos de edad para las casillas de la hoja.
     *
     * @return Collection<int, GrupoEdad>
     */
    public function gruposEdad(): Collection
    {
        return GrupoEdad::ordenados()->get();
    }

    /**
     * Categorías para las casillas de la hoja.
     *
     * @return Collection<int, CategoriaCompetencia>
     */
    public function categorias(): Collection
    {
        return CategoriaCompetencia::ordenados()->get();
    }

    /**
     * Tabla de libres (competidores → libres), para el encabezado de sparring.
     *
     * @return Collection<int, TablaLibre>
     */
    public function tablaLibres(): Collection
    {
        return TablaLibre::orderBy('competidores')->get();
    }

    /**
     * Pruebas que se puntúan con jueces: son las que tienen hoja de fórmula y
     * armas. El combate va en la hoja de sparring, que no lleva criterios.
     *
     * @return Collection<int, Prueba>
     */
    public function pruebasConCriterios(): Collection
    {
        return Prueba::has('criterios')->with('criterios')->orderBy('orden')->get();
    }
}
