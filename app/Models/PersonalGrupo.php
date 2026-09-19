<?php

namespace App\Models;

use App\Models\Concerns\PerteneceGrupo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Persona que trabaja en un grupo. Dato operativo del grupo (aislado por
 * grupo_id). Los roles concretos por grupo/sede llegarán en personal_grupo_rol.
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
}
