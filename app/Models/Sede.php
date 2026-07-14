<?php

namespace App\Models;

use App\Enums\TipoSede;
use App\Models\Concerns\PerteneceAcademia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Sede extends Model
{
    use PerteneceAcademia;

    /**
     * Atributos asignables masivamente.
     *
     * @var list<string>
     */
    protected $fillable = [
        'academia_id',
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
     * Academia dueña de la sede.
     *
     * @return BelongsTo<Academia, $this>
     */
    public function academia(): BelongsTo
    {
        return $this->belongsTo(Academia::class);
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
