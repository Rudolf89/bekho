<?php

namespace App\Models;

use App\Enums\TipoSede;
use App\Models\Concerns\PerteneceGrupo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        'nombre',
        'direccion',
        'comuna',
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
     * Instructores asignados a la sede.
     *
     * @return BelongsToMany<User, $this>
     */
    public function instructores(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'sede_user');
    }
}
