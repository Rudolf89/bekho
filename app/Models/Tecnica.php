<?php

namespace App\Models;

use App\Enums\CategoriaTecnica;
use App\Enums\ModalidadTecnica;
use App\Enums\NivelEntrenamiento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Técnica de la biblioteca del currículo ATA (catálogo compartido, sin
 * academia_id). Las técnicas con secuencia guardan sus pasos en pasos_tecnica.
 */
class Tecnica extends Model
{
    protected $table = 'tecnicas';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'categoria', 'fuente', 'subcategoria', 'nombre', 'descripcion',
        'modalidad', 'cinturon', 'nivel', 'core', 'significado', 'orden',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'categoria' => CategoriaTecnica::class,
            'modalidad' => ModalidadTecnica::class,
            'nivel' => NivelEntrenamiento::class,
            'core' => 'boolean',
        ];
    }

    /**
     * Pasos/segmentos de la técnica (formas, armas, combinaciones), en orden.
     *
     * @return HasMany<PasoTecnica, $this>
     */
    public function pasos(): HasMany
    {
        return $this->hasMany(PasoTecnica::class)->orderBy('orden');
    }

    /**
     * Grados (cinturones) a los que corresponde esta técnica.
     *
     * @return BelongsToMany<Grado, $this>
     */
    public function grados(): BelongsToMany
    {
        return $this->belongsToMany(Grado::class, 'grado_tecnica');
    }

    /**
     * @param  Builder<Tecnica>  $query
     * @return Builder<Tecnica>
     */
    public function scopeDeCategoria(Builder $query, CategoriaTecnica|string $categoria): Builder
    {
        return $query->where('categoria', $categoria instanceof CategoriaTecnica ? $categoria->value : $categoria);
    }

    /**
     * @param  Builder<Tecnica>  $query
     * @return Builder<Tecnica>
     */
    public function scopeOrdenadas(Builder $query): Builder
    {
        return $query->orderBy('categoria')->orderBy('orden');
    }
}
