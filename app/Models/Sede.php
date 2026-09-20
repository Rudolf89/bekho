<?php

namespace App\Models;

use App\Enums\TipoSede;
use App\Models\Concerns\PerteneceGrupo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sede extends Model
{
    use PerteneceGrupo;

    /**
     * Atributos asignables masivamente.
     *
     * @var list<string>
     */
    protected $fillable = [
        'grupo_id',
        'responsable_persona_id',
        'nombre',
        'direccion',
        'comuna',
        'region',
        'capacidad',
        'descuento_semestral_pct',
        'descuento_anual_pct',
        'tipo',
        'privada',
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
            'tipo' => TipoSede::class,
            'capacidad' => 'integer',
            'descuento_semestral_pct' => 'integer',
            'descuento_anual_pct' => 'integer',
            'privada' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    /**
     * Grupo dueña de la sede.
     *
     * @return BelongsTo<Grupo, $this>
     */
    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class);
    }

    /**
     * Persona responsable de la sede: origen del crédito de graduación cuando la
     * matrícula del alumno no tiene instructor asignado (regla de origen 2).
     *
     * @return BelongsTo<Persona, $this>
     */
    public function responsable(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'responsable_persona_id');
    }

    /**
     * Instructores asignados a la sede.
     *
     * @return BelongsToMany<User, $this>
     */
    public function instructores(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'sede_user');
    }

    /**
     * Tarifas de cobro de la sede (matrícula, mensualidad, … por tramo familiar).
     *
     * @return HasMany<TarifaSede, $this>
     */
    public function tarifas(): HasMany
    {
        return $this->hasMany(TarifaSede::class);
    }
}
