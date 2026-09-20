<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Federación: entidad raíz del modelo. BEKHO es una federación. Los grupos
 * (ex «grupos») y los catálogos cuelgan de ella (se conectan por fase).
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
        'clases_gracia_morosidad',
        'exencion_matricula_desde_mes',
        'exencion_matricula_hasta_mes',
        'dia_vencimiento_maximo',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'clases_gracia_morosidad' => 'integer',
            'exencion_matricula_desde_mes' => 'integer',
            'exencion_matricula_hasta_mes' => 'integer',
            'dia_vencimiento_maximo' => 'integer',
        ];
    }
}
