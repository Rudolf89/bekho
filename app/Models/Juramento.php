<?php

namespace App\Models;

use App\Enums\CategoriaJuramento;
use App\Enums\MomentoJuramento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Juramento que se recita en clase. Catálogo de la federación (sin grupo_id).
 */
class Juramento extends Model
{
    /**
     * @var string
     */
    protected $table = 'juramentos';

    /**
     * @var list<string>
     */
    protected $fillable = ['federacion_id', 'categoria_clase', 'momento', 'nombre', 'texto', 'orden', 'fuente', 'verificado'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'categoria_clase' => CategoriaJuramento::class,
            'momento' => MomentoJuramento::class,
            'orden' => 'integer',
            'verificado' => 'boolean',
        ];
    }

    /**
     * Juramentos de una categoría de clase, ordenados.
     *
     * @param  Builder<Juramento>  $query
     * @return Builder<Juramento>
     */
    public function scopeParaCategoria(Builder $query, CategoriaJuramento $categoria): Builder
    {
        return $query->where('categoria_clase', $categoria->value)->orderBy('orden');
    }

    /**
     * Juramentos que aplican a un momento (inicio incluye "ambos", cierre también).
     *
     * @param  Builder<Juramento>  $query
     * @return Builder<Juramento>
     */
    public function scopeParaMomento(Builder $query, MomentoJuramento $momento): Builder
    {
        return $query->whereIn('momento', [$momento->value, MomentoJuramento::Ambos->value]);
    }

    /**
     * @return BelongsTo<Federacion, $this>
     */
    public function federacion(): BelongsTo
    {
        return $this->belongsTo(Federacion::class);
    }
}
