<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Faceta marcial de una persona (rango, supervisión, certificación). Es
 * transversal a la federación; la pertenencia a un grupo va en personal_grupo.
 */
class Instructor extends Model
{
    /**
     * @var string
     */
    protected $table = 'instructores';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'persona_id',
        'rango_id',
        'supervisor_persona_id',
        'fecha_certificacion',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_certificacion' => 'date',
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
     * @return BelongsTo<CargoRango, $this>
     */
    public function rango(): BelongsTo
    {
        return $this->belongsTo(CargoRango::class, 'rango_id');
    }

    /**
     * Persona del supervisor directo (mismo grupo).
     *
     * @return BelongsTo<Persona, $this>
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'supervisor_persona_id');
    }

    /**
     * Instructores supervisados por esta persona.
     *
     * @return HasMany<Instructor, $this>
     */
    public function supervisados(): HasMany
    {
        return $this->hasMany(Instructor::class, 'supervisor_persona_id', 'persona_id');
    }
}
