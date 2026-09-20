<?php

namespace App\Models;

use App\Enums\EstadoLegacy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Inscripción de una PERSONA en un programa formativo (fusión de la antigua
 * inscripciones_legacy). Transversal (sin grupo_id): la formación sigue a la
 * persona aunque cambie de grupo, y los menores acceden por su apoderado.
 */
class InscripcionPrograma extends Model
{
    protected $table = 'inscripciones_programa';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'persona_id', 'programa_id', 'etapa_actual_id', 'estado',
        'fecha_ingreso', 'fecha_aprobacion', 'aprobado_por_persona_id', 'nota',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado' => EstadoLegacy::class,
            'fecha_ingreso' => 'date',
            'fecha_aprobacion' => 'date',
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
     * @return BelongsTo<Programa, $this>
     */
    public function programa(): BelongsTo
    {
        return $this->belongsTo(Programa::class);
    }

    /**
     * @return BelongsTo<EtapaPrograma, $this>
     */
    public function etapaActual(): BelongsTo
    {
        return $this->belongsTo(EtapaPrograma::class, 'etapa_actual_id');
    }

    /**
     * Persona (instructor del alumno) que aprobó el último ascenso.
     *
     * @return BelongsTo<Persona, $this>
     */
    public function aprobadoPor(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'aprobado_por_persona_id');
    }

    /**
     * @return HasMany<HoraPrograma, $this>
     */
    public function horas(): HasMany
    {
        return $this->hasMany(HoraPrograma::class)->orderByDesc('fecha');
    }

    /**
     * @return HasMany<CumplimientoRequisito, $this>
     */
    public function cumplimientos(): HasMany
    {
        return $this->hasMany(CumplimientoRequisito::class);
    }

    /**
     * @return HasMany<AscensoPrograma, $this>
     */
    public function ascensos(): HasMany
    {
        return $this->hasMany(AscensoPrograma::class)->orderByDesc('fecha');
    }

    /**
     * Total de horas acumuladas (congeladas).
     */
    public function horasAcumuladas(): float
    {
        return (float) $this->horas()->sum('horas');
    }
}
