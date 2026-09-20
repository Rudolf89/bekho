<?php

namespace App\Models;

use App\Enums\EstadoPago;
use App\Models\Concerns\PerteneceGrupo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Pago (abono con verificación). Nace "por verificar" al subir un comprobante de
 * transferencia y recepción/dirección lo confirma. Se aplica a uno o varios
 * cargos vía el pivote pago_cargo (permite abonos y pagos que cubren varios).
 */
class Pago extends Model
{
    use PerteneceGrupo;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'grupo_id',
        'pagado_por_persona_id',
        'monto',
        'fecha_pago',
        'banco',
        'referencia',
        'comprobante_archivo',
        'estado',
        'registrado_por',
        'verificado_por_user_id',
        'verificado_at',
        'motivo_anulacion',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado' => EstadoPago::class,
            'fecha_pago' => 'date',
            'verificado_at' => 'datetime',
            'monto' => 'integer',
        ];
    }

    /**
     * @param  Builder<Pago>  $query
     * @return Builder<Pago>
     */
    public function scopePorVerificar(Builder $query): Builder
    {
        return $query->where('estado', EstadoPago::PorVerificar->value);
    }

    /**
     * @param  Builder<Pago>  $query
     * @return Builder<Pago>
     */
    public function scopeVerificados(Builder $query): Builder
    {
        return $query->where('estado', EstadoPago::Verificado->value);
    }

    /**
     * Persona que pagó (apoderado responsable o alumno adulto).
     *
     * @return BelongsTo<Persona, $this>
     */
    public function pagadoPor(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'pagado_por_persona_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function verificadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verificado_por_user_id');
    }

    /**
     * Cargos que este pago cubre (total o parcialmente), con el monto aplicado.
     *
     * @return BelongsToMany<Cargo, $this>
     */
    public function cargos(): BelongsToMany
    {
        return $this->belongsToMany(Cargo::class, 'pago_cargo')
            ->withPivot('monto_aplicado')
            ->withTimestamps();
    }
}
