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
        'grado_dan_max',
        'uniforme_gala',
        'collar',
        'grupo',
    ];

    /**
     * Etiqueta legible del grado Dan del rango: un único Dan («6º Dan»), un
     * rango («2º a 5º Dan») o «—» si no aplica (rangos sin Dan).
     */
    public function danEtiqueta(): string
    {
        if ($this->grado_dan === null) {
            return '—';
        }

        if ($this->grado_dan_max !== null && $this->grado_dan_max !== $this->grado_dan) {
            return "{$this->grado_dan}º a {$this->grado_dan_max}º Dan";
        }

        return "{$this->grado_dan}º Dan";
    }

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
