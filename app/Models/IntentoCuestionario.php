<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAcademia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Intento de un usuario sobre un cuestionario (resultado autocorregido). Dato
 * operativo (con academia_id): pertenece a quien lo rindió.
 */
class IntentoCuestionario extends Model
{
    use PerteneceAcademia;

    protected $table = 'intentos_cuestionario';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'academia_id', 'user_id', 'cuestionario_id',
        'correctas', 'total', 'porcentaje', 'aprobado', 'finalizado_at',
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
}
