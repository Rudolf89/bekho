<?php

namespace App\Models;

use App\Enums\EstadoCargo;
use App\Models\Concerns\PerteneceGrupo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cargo (cobro) generado a una matrícula por un tipo de cargo y período.
 */
class Cargo extends Model
{
    use PerteneceGrupo;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'grupo_id',
        'matricula_id',
        'tipo_cargo_id',
        'periodo',
        'monto',
        'vence_el',
        'estado',
        'detalle_calculo',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'periodo' => 'date',
            'vence_el' => 'date',
            'monto' => 'integer',
            'estado' => EstadoCargo::class,
            'detalle_calculo' => 'array',
        ];
    }

    /**
     * @param  Builder<Cargo>  $query
     * @return Builder<Cargo>
     */
    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('estado', EstadoCargo::Pendiente->value);
    }

    /**
     * @return BelongsTo<Matricula, $this>
     */
    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class);
    }

    /**
     * @return BelongsTo<TipoCargo, $this>
     */
    public function tipoCargo(): BelongsTo
    {
        return $this->belongsTo(TipoCargo::class);
    }
}
