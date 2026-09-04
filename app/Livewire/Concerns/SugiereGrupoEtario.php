<?php

namespace App\Livewire\Concerns;

use App\Enums\GrupoEtario;
use Illuminate\Support\Carbon;

/**
 * Lógica compartida de grupo etario para los formularios de alumno (inscripción
 * y alta rápida): calcula la edad desde la fecha de nacimiento, sugiere el grupo
 * y avisa cuando el alumno está por cumplir la edad que lo pasa al grupo
 * siguiente. El campo `grupo_etario` siempre queda editable a mano (por el
 * solapamiento a los 12 años).
 *
 * El componente debe exponer las propiedades públicas `?string $fecha_nacimiento`
 * y `string $grupo_etario`.
 */
trait SugiereGrupoEtario
{
    /**
     * Al cambiar la fecha de nacimiento, sugiere el grupo etario por edad.
     */
    public function updatedFechaNacimiento(): void
    {
        $edad = $this->edad();

        if ($edad !== null) {
            $this->grupo_etario = GrupoEtario::sugerirPorEdad($edad)->value;
        }
    }

    /**
     * Edad actual del alumno (años cumplidos), o null si no hay fecha válida.
     */
    public function edad(): ?int
    {
        if (! $this->fecha_nacimiento) {
            return null;
        }

        try {
            return (int) Carbon::parse($this->fecha_nacimiento)->age;
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Meses hasta el próximo cumpleaños (0 si es este mes), o null si no hay fecha.
     */
    protected function mesesHastaProximoCumple(): ?int
    {
        if (! $this->fecha_nacimiento) {
            return null;
        }

        try {
            $nacimiento = Carbon::parse($this->fecha_nacimiento);
        } catch (\Exception) {
            return null;
        }

        $proximo = $nacimiento->copy()->year(now()->year);
        if ($proximo->lessThan(now()->startOfDay())) {
            $proximo->addYear();
        }

        return (int) floor(now()->startOfDay()->diffInMonths($proximo));
    }

    /**
     * Si el alumno está por cumplir (dentro de ~4 meses) la edad que lo pasaría
     * al grupo siguiente, devuelve esa sugerencia para ofrecer el cambio.
     *
     * @return array{grupo: GrupoEtario, meses: int, edadProxima: int}|null
     */
    public function sugerenciaProximoGrupo(): ?array
    {
        $edad = $this->edad();

        if ($edad === null || $this->grupo_etario === '') {
            return null;
        }

        $siguiente = GrupoEtario::from($this->grupo_etario)->siguiente();
        if (! $siguiente) {
            return null;
        }

        $meses = $this->mesesHastaProximoCumple();
        if ($meses === null || $meses > 4) {
            return null;
        }

        $edadProxima = $edad + 1;
        if ($edadProxima < $siguiente->edadMinima()) {
            return null;
        }

        return ['grupo' => $siguiente, 'meses' => $meses, 'edadProxima' => $edadProxima];
    }

    /**
     * Cambia el grupo etario elegido (usado por la sugerencia de "pasar al
     * grupo siguiente" cuando el alumno está por cumplir la edad).
     */
    public function cambiarGrupo(string $grupo): void
    {
        if (GrupoEtario::tryFrom($grupo)) {
            $this->grupo_etario = $grupo;
        }
    }
}
