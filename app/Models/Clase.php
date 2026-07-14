<?php

namespace App\Models;

use App\Enums\DiaSemana;
use App\Enums\GrupoEtario;
use App\Enums\PapelEnClase;
use App\Models\Concerns\PerteneceAcademia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'planilla_id',
        'nombre',
        'grupo_etario',
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
     * Instructor titular a cargo (columna heredada; el pivote clase_instructor
     * es la fuente de verdad para el conjunto de instructores).
     *
     * @return BelongsTo<User, $this>
     */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    /**
     * Instructores asignados a la clase (uno o varios), con su papel.
     *
     * @return BelongsToMany<User, $this>
     */
    public function instructores(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'clase_instructor')
            ->withPivot('papel')
            ->withTimestamps();
    }

    /**
     * Sincroniza los instructores de la clase manteniendo su papel y refleja
     * al titular en la columna heredada instructor_id.
     *
     * @param  array<int, string>  $papelesPorUsuario  [user_id => papel]
     */
    public function sincronizarInstructores(array $papelesPorUsuario): void
    {
        $sync = [];
        $titular = null;

        foreach ($papelesPorUsuario as $userId => $papel) {
            $sync[$userId] = ['papel' => $papel];

            if ($papel === PapelEnClase::Titular->value && $titular === null) {
                $titular = (int) $userId;
            }
        }

        $this->instructores()->sync($sync);

        $this->forceFill(['instructor_id' => $titular])->save();
    }

    /**
     * @return HasMany<Asistencia, $this>
     */
    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class);
    }

    /**
     * Planilla (rutina) que le corresponde a la clase.
     *
     * @return BelongsTo<Planilla, $this>
     */
    public function planilla(): BelongsTo
    {
        return $this->belongsTo(Planilla::class);
    }

    /**
     * Estudiantes que corresponden a esta clase: activos de la misma sede y
     * grupo etario (las clases se dividen solo por grupo etario; no hay
     * inscripción explícita alumno↔clase).
     *
     * @return Builder<Estudiante>
     */
    public function estudiantesEsperados(): Builder
    {
        return Estudiante::query()
            ->activos()
            ->where('sede_id', $this->sede_id)
            ->where('grupo_etario', $this->grupo_etario->value)
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
