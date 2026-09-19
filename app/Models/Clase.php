<?php

namespace App\Models;

use App\Enums\GrupoEtario;
use App\Enums\PapelEnClase;
use App\Models\Concerns\PerteneceGrupo;
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
    use PerteneceGrupo;

    /**
     * @var string
     */
    protected $table = 'clases';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'grupo_id',
        'sede_id',
        'instructor_id',
        'planilla_id',
        'nombre',
        'grupo_etario',
        'cupo_maximo',
        'activo',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'grupo_etario' => GrupoEtario::class,
            'cupo_maximo' => 'integer',
            'activo' => 'boolean',
        ];
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
     * Horarios (día + hora) de la clase. Una clase puede reunirse varios días.
     *
     * @return HasMany<HorarioClase, $this>
     */
    public function horarios(): HasMany
    {
        return $this->hasMany(HorarioClase::class)
            ->orderBy('dia_semana')
            ->orderBy('hora_inicio');
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
     * Planilla (rutina) que le corresponde a la clase. La planilla es contenido
     * transversal compartido; la clase solo la referencia.
     *
     * @return BelongsTo<Planilla, $this>
     */
    public function planilla(): BelongsTo
    {
        return $this->belongsTo(Planilla::class);
    }

    /**
     * Ejercicios de calentamiento que el instructor armó para esta clase.
     *
     * @return BelongsToMany<EjercicioCalentamiento, $this>
     */
    public function calentamiento(): BelongsToMany
    {
        return $this->belongsToMany(
            EjercicioCalentamiento::class,
            'calentamiento_clase',
            'clase_id',
            'ejercicio_calentamiento_id',
        )->withPivot('orden')->orderByPivot('orden')->withTimestamps();
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
     * Matrículas que corresponden a esta clase: activas de la misma sede y grupo
     * etario (las clases se dividen solo por grupo etario). Reemplaza a
     * estudiantesEsperados en el modelo de personas/matrículas.
     *
     * @return Builder<Matricula>
     */
    public function matriculasEsperadas(): Builder
    {
        return Matricula::query()
            ->activas()
            ->where('sede_id', $this->sede_id)
            ->where('grupo_etario', $this->grupo_etario->value)
            ->with('persona')
            ->join('personas', 'personas.id', '=', 'matriculas.persona_id')
            ->orderBy('personas.nombres')
            ->select('matriculas.*');
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
