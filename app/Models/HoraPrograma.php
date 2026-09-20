<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Horas acreditadas de una inscripción a programa (fusión de horas_legacy).
 * Congeladas al registrarse (no se recalculan). Por ahora todas de origen
 * 'manual'; el cálculo desde la asistencia con papel de ayudante es follow-up.
 */
class HoraPrograma extends Model
{
    protected $table = 'horas_programa';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'inscripcion_programa_id', 'fecha', 'horas', 'origen', 'descripcion', 'registrado_por_user_id',
    ];

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
     * @return BelongsTo<InscripcionPrograma, $this>
     */
    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(InscripcionPrograma::class, 'inscripcion_programa_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_user_id');
    }
}
