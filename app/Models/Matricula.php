<?php

namespace App\Models;

use App\Enums\EstadoMatricula;
use App\Models\Concerns\PerteneceGrupo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Matrícula: vínculo alumno ↔ grupo. Reemplaza a estudiantes en la operación
 * (fases posteriores). Una persona solo puede tener una matrícula activa.
 */
class Matricula extends Model
{
    use PerteneceGrupo, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'persona_id',
        'grupo_id',
        'sede_id',
        'estado',
        'fecha_ingreso',
        'fecha_retiro',
        'motivo_baja',
        'instructor_persona_id',
        'dia_vencimiento',
        'acepto_reglamento_at',
        'aceptado_por_user_id',
        'matricula_origen_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado' => EstadoMatricula::class,
            'fecha_ingreso' => 'date',
            'fecha_retiro' => 'date',
            'acepto_reglamento_at' => 'datetime',
            'dia_vencimiento' => 'integer',
        ];
    }

    /**
     * @param  Builder<Matricula>  $query
     * @return Builder<Matricula>
     */
    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('estado', EstadoMatricula::Activa->value);
    }

    /**
     * @return BelongsTo<Persona, $this>
     */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    /**
     * @return BelongsTo<Grupo, $this>
     */
    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class);
    }

    /**
     * @return BelongsTo<Sede, $this>
     */
    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class);
    }

    /**
     * Persona del instructor a cargo del alumno.
     *
     * @return BelongsTo<Persona, $this>
     */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'instructor_persona_id');
    }

    /**
     * Usuario que aceptó el reglamento en nombre del alumno (o su apoderado).
     *
     * @return BelongsTo<User, $this>
     */
    public function aceptadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aceptado_por_user_id');
    }

    /**
     * Matrícula de origen si esta nació de un traslado.
     *
     * @return BelongsTo<Matricula, $this>
     */
    public function origen(): BelongsTo
    {
        return $this->belongsTo(Matricula::class, 'matricula_origen_id');
    }
}
