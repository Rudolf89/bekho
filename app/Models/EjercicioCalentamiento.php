<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ejercicio de la biblioteca de calentamiento (catálogo compartido).
 */
class EjercicioCalentamiento extends Model
{
    protected $table = 'ejercicios_calentamiento';

    /**
     * @var list<string>
     */
    protected $fillable = ['categoria_calentamiento_id', 'nombre', 'descripcion', 'orden'];

    /**
     * @return BelongsTo<CategoriaCalentamiento, $this>
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaCalentamiento::class, 'categoria_calentamiento_id');
    }
}
