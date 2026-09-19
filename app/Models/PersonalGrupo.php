<?php

namespace App\Models;

use App\Models\Concerns\PerteneceGrupo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Models\Role;

/**
 * Persona que trabaja en un grupo. Dato operativo del grupo (aislado por
 * grupo_id). Sus roles concretos por grupo/sede viven en personal_grupo_rol.
 */
class PersonalGrupo extends Model
{
    use PerteneceGrupo;

    /**
     * @var string
     */
    protected $table = 'personal_grupo';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'persona_id',
        'grupo_id',
        'activo',
        'fecha_ingreso',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'fecha_ingreso' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Persona, $this>
     */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    /**
     * @return BelongsTo<Grupo, $this>
     */
    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class);
    }

    /**
     * Roles del personal en este grupo (con o sin sede acotada).
     *
     * @return HasMany<PersonalGrupoRol, $this>
     */
    public function roles(): HasMany
    {
        return $this->hasMany(PersonalGrupoRol::class);
    }

    /**
     * Otorga un rol al personal, opcionalmente acotado a una sede. Idempotente.
     */
    public function otorgarRol(string|Role $rol, ?int $sedeId = null): PersonalGrupoRol
    {
        $role = $rol instanceof Role ? $rol : Role::findByName($rol);

        return $this->roles()->updateOrCreate(
            ['role_id' => $role->id, 'sede_id' => $sedeId],
            [],
        );
    }
}
