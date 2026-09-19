<?php

namespace App\Models;

use App\Enums\Parentesco;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vínculo apoderado ↔ alumno. Puede cruzar grupos. El apoderado debe ser mayor
 * de edad (se valida en la aplicación).
 */
class Tutela extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'apoderado_persona_id',
        'alumno_persona_id',
        'parentesco',
        'responsable_pago',
        'vigente_desde',
        'vigente_hasta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'parentesco' => Parentesco::class,
            'responsable_pago' => 'boolean',
            'vigente_desde' => 'date',
            'vigente_hasta' => 'date',
        ];
    }

    /**
     * ¿La tutela está vigente a la fecha dada (hoy por defecto)?
     */
    public function estaVigente(?\DateTimeInterface $a = null): bool
    {
        $a = $a ?? now();

        return ($this->vigente_desde === null || $this->vigente_desde <= $a)
            && ($this->vigente_hasta === null || $this->vigente_hasta >= $a);
    }

    /**
     * @return BelongsTo<Persona, $this>
     */
    public function apoderado(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'apoderado_persona_id');
    }

    /**
     * @return BelongsTo<Persona, $this>
     */
    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'alumno_persona_id');
    }
}
