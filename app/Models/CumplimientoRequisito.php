<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cumplimiento de un requisito de etapa por una inscripción (fusión de
 * cumplimiento_requisitos). El instructor del alumno lo marca; los requisitos de
 * tipo cuestionario se cumplen solos con un intento aprobado (no se marcan aquí).
 */
class CumplimientoRequisito extends Model
{
    protected $table = 'cumplimientos_requisito';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'inscripcion_programa_id', 'requisito_etapa_id', 'cumplido_at', 'aprobado_por_persona_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['cumplido_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<InscripcionPrograma, $this>
     */
    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(InscripcionPrograma::class, 'inscripcion_programa_id');
    }

    /**
     * @return BelongsTo<RequisitoEtapa, $this>
     */
    public function requisito(): BelongsTo
    {
        return $this->belongsTo(RequisitoEtapa::class, 'requisito_etapa_id');
    }

    /**
     * @return BelongsTo<Persona, $this>
     */
    public function aprobadoPor(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'aprobado_por_persona_id');
    }
}
