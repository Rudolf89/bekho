<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role;

/**
 * Rol del personal en un grupo, opcionalmente acotado a una sede. Con sede_id el
 * rol solo aplica en esa sede (p. ej. direccion-sede); sin sede_id, en todo el
 * grupo. Una persona puede tener varios roles en el mismo grupo.
 */
class PersonalGrupoRol extends Model
{
    /**
     * @var string
     */
    protected $table = 'personal_grupo_rol';

    /**
     * @var list<string>
     */
    protected $fillable = ['personal_grupo_id', 'role_id', 'sede_id'];

    /**
     * @return BelongsTo<PersonalGrupo, $this>
     */
    public function personalGrupo(): BelongsTo
    {
        return $this->belongsTo(PersonalGrupo::class);
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * @return BelongsTo<Sede, $this>
     */
    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class);
    }
}
