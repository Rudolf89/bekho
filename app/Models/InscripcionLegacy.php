<?php

namespace App\Models;

use App\Enums\EstadoIntento;
use App\Enums\EstadoLegacy;
use App\Models\Concerns\PerteneceAcademia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Inscripción de un usuario (instructor en formación) en un nivel del Programa
 * Legacy. Dato operativo (con academia_id). Acumula horas y el cumplimiento de
 * requisitos; el ascenso lo aprueba el licenciatario.
 */
class InscripcionLegacy extends Model
{
    use PerteneceAcademia;

    protected $table = 'inscripciones_legacy';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'academia_id', 'user_id', 'nivel_legacy_id', 'estado',
        'fecha_inicio', 'fecha_aprobacion', 'aprobado_por', 'nota',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado' => EstadoLegacy::class,
            'fecha_inicio' => 'date',
            'fecha_aprobacion' => 'date',
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
     * @return BelongsTo<NivelLegacy, $this>
     */
    public function nivel(): BelongsTo
    {
        return $this->belongsTo(NivelLegacy::class, 'nivel_legacy_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function aprobadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    /**
     * @return HasMany<HoraLegacy, $this>
     */
    public function horas(): HasMany
    {
        return $this->hasMany(HoraLegacy::class, 'inscripcion_legacy_id')->orderByDesc('fecha');
    }

    /**
     * Requisitos marcados manualmente como cumplidos.
     *
     * @return BelongsToMany<RequisitoLegacy, $this>
     */
    public function requisitosCumplidos(): BelongsToMany
    {
        return $this->belongsToMany(RequisitoLegacy::class, 'cumplimiento_requisitos', 'inscripcion_legacy_id', 'requisito_legacy_id')
            ->withPivot(['verificado_por', 'verificado_at'])
            ->withTimestamps();
    }

    /**
     * Total de horas acumuladas.
     */
    public function horasAcumuladas(): float
    {
        return (float) $this->horas()->sum('horas');
    }

    /**
     * ¿Completó las horas requeridas del nivel?
     */
    public function horasCompletas(): bool
    {
        return $this->horasAcumuladas() >= ($this->nivel?->horas_requeridas ?? 0);
    }

    /**
     * ¿Está cumplido un requisito? Los de cuestionario se cumplen solos cuando
     * el usuario tiene un intento APROBADO; el resto, por marca manual.
     */
    public function cumpleRequisito(RequisitoLegacy $requisito): bool
    {
        if ($requisito->esAutomatico()) {
            return IntentoCuestionario::query()
                ->where('user_id', $this->user_id)
                ->where('cuestionario_id', $requisito->cuestionario_id)
                ->where('estado', EstadoIntento::Aprobado)
                ->exists();
        }

        return $this->requisitosCumplidos->contains('id', $requisito->id);
    }

    /**
     * ¿Cumple TODOS los requisitos del nivel?
     */
    public function cumpleTodosLosRequisitos(): bool
    {
        return $this->nivel->requisitos->every(fn (RequisitoLegacy $r) => $this->cumpleRequisito($r));
    }

    /**
     * ¿Reúne las condiciones para que el licenciatario apruebe el ascenso?
     */
    public function puedeAprobar(): bool
    {
        return $this->estado === EstadoLegacy::EnCurso
            && $this->horasCompletas()
            && $this->cumpleTodosLosRequisitos();
    }
}
