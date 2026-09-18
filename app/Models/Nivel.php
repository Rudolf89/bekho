<?php

namespace App\Models;

use App\Models\Concerns\PerteneceGrupo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Nivel extends Model
{
    use PerteneceGrupo;

    /**
     * Tabla asociada (plural irregular en español).
     *
     * @var string
     */
    protected $table = 'niveles';

    /**
     * Atributos asignables masivamente.
     *
     * @var list<string>
     */
    protected $fillable = [
        'grupo_id',
        'nombre',
        'descripcion',
        'orden',
        'activo',
    ];

    /**
     * Casts de atributos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    /**
     * Grupo dueña del nivel.
     *
     * @return BelongsTo<Grupo, $this>
     */
    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class);
    }

    /**
     * Contenidos del nivel, ordenados.
     *
     * @return HasMany<Contenido, $this>
     */
    public function contenidos(): HasMany
    {
        return $this->hasMany(Contenido::class)->orderBy('orden');
    }

    /**
     * Solo niveles activos.
     *
     * @param  Builder<Nivel>  $query
     * @return Builder<Nivel>
     */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    /**
     * Ordenados por el campo orden.
     *
     * @param  Builder<Nivel>  $query
     * @return Builder<Nivel>
     */
    public function scopeOrdenados(Builder $query): Builder
    {
        return $query->orderBy('orden');
    }
}
