<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Federación: entidad raíz del modelo. BEKHO es una federación. Los grupos
 * (ex «academias») y los catálogos cuelgan de ella (se conectan por fase).
 */
class Federacion extends Model
{
    /**
     * Tabla asociada (el plural natural no es «federacions»).
     */
    protected $table = 'federaciones';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nombre',
        'razon_social',
        'pais',
        'moneda',
        'logo',
        'activo',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}
