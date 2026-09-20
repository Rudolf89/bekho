<?php

namespace App\Models;

use App\Enums\EstadoCargo;
use App\Enums\EstadoPago;
use App\Models\Concerns\PerteneceGrupo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        'sede_id',
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
     * Sede que cobra el cargo (la de la matrícula).
     *
     * @return BelongsTo<Sede, $this>
     */
    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class);
    }

    /**
     * @return BelongsTo<TipoCargo, $this>
     */
    public function tipoCargo(): BelongsTo
    {
        return $this->belongsTo(TipoCargo::class);
    }

    /**
     * Pagos aplicados a este cargo (con el monto aplicado de cada uno).
     *
     * @return BelongsToMany<Pago, $this>
     */
    public function pagos(): BelongsToMany
    {
        return $this->belongsToMany(Pago::class, 'pago_cargo')
            ->withPivot('monto_aplicado')
            ->withTimestamps();
    }

    /**
     * Monto ya cubierto por pagos VERIFICADOS (los "por verificar" no cuentan).
     */
    public function montoPagadoVerificado(): int
    {
        return (int) $this->pagos()
            ->where('estado', EstadoPago::Verificado->value)
            ->sum('pago_cargo.monto_aplicado');
    }

    /**
     * ¿El cargo está totalmente cubierto por pagos verificados?
     */
    public function estaCubierto(): bool
    {
        return $this->montoPagadoVerificado() >= $this->monto;
    }
}
