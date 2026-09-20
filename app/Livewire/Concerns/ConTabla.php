<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

/**
 * Utilidades comunes de tablas Livewire: búsqueda de texto + ordenamiento
 * (asc/desc). El resumen de totales (conteo de filas y sumas de montos) lo
 * calcula cada componente en su `render()` sobre la colección ya filtrada y se
 * muestra con el componente Blade <x-tabla.resumen>.
 *
 * Incluye `ConOrden` (ordenarPor/aplicarOrden). Cada tabla decide qué columnas
 * son buscables pasándolas a `aplicarBusqueda()`; admite columnas de relaciones
 * con notación de punto ("estudiante.nombre").
 */
trait ConTabla
{
    use ConOrden;

    #[Url]
    public string $buscar = '';

    /**
     * Al escribir en el buscador se vuelve a la primera página (si pagina).
     */
    public function updatedBuscar(): void
    {
        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    /**
     * Aplica el término de búsqueda como LIKE sobre las columnas indicadas.
     * Una columna con punto ("relacion.columna") busca dentro de la relación.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @param  list<string>  $columnas
     * @return Builder<covariant \Illuminate\Database\Eloquent\Model>
     */
    public function aplicarBusqueda(Builder $query, array $columnas): Builder
    {
        $termino = trim($this->buscar);

        if ($termino === '' || $columnas === []) {
            return $query;
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $termino).'%';

        return $query->where(function (Builder $q) use ($columnas, $like) {
            foreach ($columnas as $columna) {
                $this->aplicarColumnaBusqueda($q, $columna, $like, true);
            }
        });
    }

    /**
     * Aplica un LIKE sobre una columna que puede pertenecer a una relación
     * anidada ("matricula.persona.nombres"): baja por cada nivel con whereHas.
     */
    protected function aplicarColumnaBusqueda(Builder $q, string $columna, string $like, bool $or): void
    {
        if (str_contains($columna, '.')) {
            [$relacion, $campo] = explode('.', $columna, 2);
            $metodo = $or ? 'orWhereHas' : 'whereHas';
            // Dentro de la relación las condiciones se encadenan con AND (where).
            $q->{$metodo}($relacion, fn (Builder $r) => $this->aplicarColumnaBusqueda($r, $campo, $like, false));

            return;
        }

        $q->{$or ? 'orWhere' : 'where'}($columna, 'like', $like);
    }
}
