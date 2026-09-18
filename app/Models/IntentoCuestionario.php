<?php

namespace App\Models;

use App\Enums\EstadoIntento;
use App\Models\Concerns\PerteneceGrupo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Intento de un usuario sobre un cuestionario (resultado autocorregido). Dato
 * operativo (con grupo_id): pertenece a quien lo rindió.
 */
class IntentoCuestionario extends Model
{
    use PerteneceGrupo;

    protected $table = 'intentos_cuestionario';

    /**
     * Valores por defecto (el intento nace en revisión).
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'estado' => 'pendiente',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'grupo_id', 'user_id', 'cuestionario_id',
        'correctas', 'total', 'porcentaje', 'aprobado', 'finalizado_at',
        'estado', 'revisado_por', 'revisado_at', 'justificacion',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'correctas' => 'integer',
            'total' => 'integer',
            'porcentaje' => 'integer',
            'aprobado' => 'boolean',
            'finalizado_at' => 'datetime',
            'estado' => EstadoIntento::class,
            'revisado_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Cuestionario, $this>
     */
    public function cuestionario(): BelongsTo
    {
        return $this->belongsTo(Cuestionario::class);
    }

    /**
     * @return HasMany<RespuestaIntento, $this>
     */
    public function respuestas(): HasMany
    {
        return $this->hasMany(RespuestaIntento::class, 'intento_id');
    }

    /**
     * Examinador que revisó el intento.
     *
     * @return BelongsTo<User, $this>
     */
    public function revisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }

    /**
     * ¿Aprobar este intento sería una excepción (no alcanzó el umbral)? En ese
     * caso la justificación del examinador es obligatoria.
     */
    public function requiereJustificacion(): bool
    {
        return ! $this->aprobado;
    }

    /**
     * @param  Builder<IntentoCuestionario>  $query
     * @return Builder<IntentoCuestionario>
     */
    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('estado', EstadoIntento::Pendiente);
    }
}
