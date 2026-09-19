<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Categoría de competencia (color o negro), catálogo de la federación.
 */
class CategoriaCompetencia extends Model
{
    /**
     * @var string
     */
    protected $table = 'categorias_competencia';

    /**
     * @var list<string>
     */
    protected $fillable = ['federacion_id', 'nombre', 'tipo', 'orden'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['orden' => 'integer'];
    }

    /**
     * @param  Builder<CategoriaCompetencia>  $query
     * @return Builder<CategoriaCompetencia>
     */
    public function scopeOrdenados(Builder $query): Builder
    {
        return $query->orderBy('orden');
    }

    /**
     * @return BelongsTo<Federacion, $this>
     */
    public function federacion(): BelongsTo
    {
        return $this->belongsTo(Federacion::class);
    }
}
