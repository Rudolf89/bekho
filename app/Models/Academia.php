<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Academia extends Model
{
    /**
     * Atributos asignables masivamente.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nombre',
        'logo',
        'email',
        'telefono',
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
     * Sedes de la academia.
     *
     * @return HasMany<Sede, $this>
     */
    public function sedes(): HasMany
    {
        return $this->hasMany(Sede::class);
    }

    /**
     * Usuarios de la academia.
     *
     * @return HasMany<User, $this>
     */
    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
