<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catálogo compartido de cargos/rangos. NO usa el trait PerteneceAcademia:
 * es transversal a todas las academias.
 */
class CargoRango extends Model
{
    /**
     * Tabla asociada.
     *
     * @var string
     */
    protected $table = 'cargos_rangos';

    /**
     * Atributos asignables masivamente.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nombre',
        'nivel',
        'grado_dan',
        'uniforme_gala',
        'collar',
        'grupo',
    ];

    /**
     * Usuarios con este rango.
     *
     * @return HasMany<User, $this>
     */
    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class, 'rango_id');
    }
}
