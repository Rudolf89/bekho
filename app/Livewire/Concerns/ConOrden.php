<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Ordenamiento reutilizable para tablas Livewire. Expone `ordenarPor()` para los
 * encabezados y `aplicarOrden()` para la consulta.
 */
trait ConOrden
{
    public string $ordenCampo = '';

    public string $ordenDir = 'asc';

    /**
     * Alterna el orden por un campo (asc ↔ desc; nuevo campo empieza en asc).
     */
    public function ordenarPor(string $campo): void
    {
        if ($this->ordenCampo === $campo) {
            $this->ordenDir = $this->ordenDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->ordenCampo = $campo;
            $this->ordenDir = 'asc';
        }

        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    /**
     * Aplica el orden a la consulta, validando contra los campos permitidos.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @param  list<string>  $permitidos
     */
    public function aplicarOrden(Builder $query, array $permitidos, string $porDefecto): Builder
    {
        $campo = in_array($this->ordenCampo, $permitidos, true) ? $this->ordenCampo : $porDefecto;
        $dir = $this->ordenDir === 'desc' ? 'desc' : 'asc';

        return $query->orderBy($campo, $dir);
    }
}
