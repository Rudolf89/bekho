<?php

namespace App\Models;

use App\Models\Concerns\PerteneceGrupo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tarifa de un grupo para un tipo de cargo, por tramo de cantidad de alumnos.
 */
class TarifaGrupo extends Model
{
    use PerteneceGrupo;

    /**
     * @var string
     */
    protected $table = 'tarifas_grupo';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'grupo_id',
        'tipo_cargo_id',
        'cantidad_alumnos',
        'monto_por_alumno',
        'vigente_desde',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cantidad_alumnos' => 'integer',
            'monto_por_alumno' => 'integer',
            'vigente_desde' => 'date',
        ];
    }

    /**
     * @return BelongsTo<TipoCargo, $this>
     */
    public function tipoCargo(): BelongsTo
    {
        return $this->belongsTo(TipoCargo::class);
    }
}
