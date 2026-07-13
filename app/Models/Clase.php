<?php

namespace App\Models;

use App\Enums\DiaSemana;
use App\Enums\GrupoEtario;
use App\Enums\NivelEntrenamiento;
use App\Models\Concerns\PerteneceAcademia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Clase recurrente del horario.
 */
class Clase extends Model
{
    use PerteneceAcademia;

    /**
     * @var string
     */
    protected $table = 'clases';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'academia_id',
        'sede_id',
        'instructor_id',
        'nombre',
        'grupo_etario',
        'nivel',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
        'activo',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'grupo_etario' => GrupoEtario::class,
            'nivel' => NivelEntrenamiento::class,
            'dia_semana' => DiaSemana::class,
            'activo' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Academia, $this>
     */
    public function academia(): BelongsTo
    {
        return $this->belongsTo(Academia::class);
    }

    /**
     * @return BelongsTo<Sede, $this>
     */
    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class);
    }

    /**
     * Instructor a cargo.
     *
     * @return BelongsTo<User, $this>
     */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    /**
     * @return HasMany<Asistencia, $this>
     */
    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class);
    }

    /**
     * Estudiantes que corresponden a esta clase: activos de la misma sede,
     * grupo etario y nivel (no hay inscripción explícita alumno↔clase).
     *
     * @return Builder<Estudiante>
     */
    public function estudiantesEsperados(): Builder
    {
        return Estudiante::query()
            ->activos()
            ->where('sede_id', $this->sede_id)
            ->where('grupo_etario', $this->grupo_etario->value)
            ->where('nivel', $this->nivel->value)
            ->orderBy('nombre');
    }

    /**
     * @param  Builder<Clase>  $query
     * @return Builder<Clase>
     */
    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activo', true);
    }
}
