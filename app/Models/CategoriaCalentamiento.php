<?php

namespace App\Models;

use App\Enums\GrupoEtario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Categoría de la biblioteca de calentamiento. Catálogo compartido (sin
 * grupo_id). No todas las categorías aplican a todos los grupos etarios: la
 * restricción se guarda en el pivote `categoria_calentamiento_grupo`.
 */
class CategoriaCalentamiento extends Model
{
    protected $table = 'categorias_calentamiento';

    /**
     * @var list<string>
     */
    protected $fillable = ['clave', 'nombre', 'color', 'orden', 'fuente', 'verificado'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['verificado' => 'boolean'];
    }

    /**
     * Ejercicios de la categoría, ordenados.
     *
     * @return HasMany<EjercicioCalentamiento, $this>
     */
    public function ejercicios(): HasMany
    {
        return $this->hasMany(EjercicioCalentamiento::class)->orderBy('orden');
    }

    /**
     * Grupos etarios a los que aplica la categoría.
     *
     * @return list<string>
     */
    public function gruposEtarios(): array
    {
        return DB::table('categoria_calentamiento_grupo')
            ->where('categoria_calentamiento_id', $this->id)
            ->pluck('grupo_etario')
            ->all();
    }

    /**
     * Fija los grupos etarios a los que aplica la categoría (idempotente).
     *
     * @param  array<int, GrupoEtario|string>  $grupos
     */
    public function sincronizarGrupos(array $grupos): void
    {
        $valores = array_map(fn ($g) => $g instanceof GrupoEtario ? $g->value : $g, $grupos);

        DB::table('categoria_calentamiento_grupo')
            ->where('categoria_calentamiento_id', $this->id)
            ->whereNotIn('grupo_etario', $valores)
            ->delete();

        foreach ($valores as $grupo) {
            DB::table('categoria_calentamiento_grupo')->updateOrInsert(
                ['categoria_calentamiento_id' => $this->id, 'grupo_etario' => $grupo],
                ['updated_at' => now(), 'created_at' => now()],
            );
        }
    }

    /**
     * Solo las categorías que aplican al grupo etario dado.
     *
     * @param  Builder<CategoriaCalentamiento>  $query
     * @return Builder<CategoriaCalentamiento>
     */
    public function scopeParaGrupo(Builder $query, GrupoEtario|string $grupo): Builder
    {
        $valor = $grupo instanceof GrupoEtario ? $grupo->value : $grupo;

        return $query->whereExists(fn ($sub) => $sub
            ->from('categoria_calentamiento_grupo')
            ->whereColumn('categoria_calentamiento_id', 'categorias_calentamiento.id')
            ->where('grupo_etario', $valor));
    }

    /**
     * @param  Builder<CategoriaCalentamiento>  $query
     * @return Builder<CategoriaCalentamiento>
     */
    public function scopeOrdenadas(Builder $query): Builder
    {
        return $query->orderBy('orden');
    }
}
