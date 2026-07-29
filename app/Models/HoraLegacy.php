<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de horas de una inscripción Legacy (hacia las 100 h del nivel).
 * Cuelga de una inscripción (operativo).
 */
class HoraLegacy extends Model
{
    protected $table = 'horas_legacy';

    /**
     * @var list<string>
     */
    protected $fillable = ['inscripcion_legacy_id', 'fecha', 'horas', 'descripcion', 'verificado_por'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'horas' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<InscripcionLegacy, $this>
     */
    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(InscripcionLegacy::class, 'inscripcion_legacy_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function verificadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verificado_por');
    }
}
