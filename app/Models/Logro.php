<?php

namespace App\Models;

use App\Models\Concerns\PerteneceGrupo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Logro: una recompensa ganada por un alumno. Dato operativo (con grupo_id).
 */
class Logro extends Model
{
    use PerteneceGrupo;

    protected $table = 'logros';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'grupo_id', 'estudiante_id', 'recompensa_id', 'otorgado_por', 'nota', 'otorgado_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['otorgado_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<Estudiante, $this>
     */
    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }

    /**
     * @return BelongsTo<Recompensa, $this>
     */
    public function recompensa(): BelongsTo
    {
        return $this->belongsTo(Recompensa::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function otorgadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'otorgado_por');
    }
}
