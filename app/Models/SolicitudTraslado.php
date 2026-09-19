<?php

namespace App\Models;

use App\Enums\EstadoTraslado;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Solicitud de traslado de un alumno entre grupos. Cruza dos grupos (origen y
 * destino), por eso no lleva grupo_id.
 */
class SolicitudTraslado extends Model
{
    /**
     * @var string
     */
    protected $table = 'solicitudes_traslado';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'persona_id',
        'matricula_origen_id',
        'grupo_destino_id',
        'sede_destino_id',
        'estado',
        'solicitada_por_user_id',
        'consentido_por_persona_id',
        'consentimiento_at',
        'consentimiento_medio',
        'consentimiento_respaldo',
        'resuelta_por_persona_id',
        'motivo',
        'plazo_desde',
        'dias_consumidos',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado' => EstadoTraslado::class,
            'consentimiento_at' => 'datetime',
            'plazo_desde' => 'date',
            'dias_consumidos' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Persona, $this>
     */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    /**
     * @return BelongsTo<Matricula, $this>
     */
    public function matriculaOrigen(): BelongsTo
    {
        return $this->belongsTo(Matricula::class, 'matricula_origen_id');
    }

    /**
     * @return BelongsTo<Grupo, $this>
     */
    public function grupoDestino(): BelongsTo
    {
        return $this->belongsTo(Grupo::class, 'grupo_destino_id');
    }

    /**
     * @return BelongsTo<Sede, $this>
     */
    public function sedeDestino(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'sede_destino_id');
    }
}
