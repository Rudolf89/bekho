<?php

namespace App\Models;

use App\Enums\EscalaGrado;
use App\Enums\Genero;
use App\Enums\GrupoEtario;
use App\Enums\NivelEntrenamiento;
use App\Models\Concerns\PerteneceGrupo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * Ficha del alumno. Distinta del User: el estudiante puede o no tener cuenta.
 */
class Estudiante extends Model
{
    use PerteneceGrupo;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'grupo_id',
        'user_id',
        'sede_id',
        'instructor_id',
        'grado_id',
        'nombre',
        'rut',
        'fecha_nacimiento',
        'genero',
        'direccion',
        'region',
        'comuna',
        'grupo_etario',
        'nivel',
        'apoderado_1',
        'apoderado_2',
        'telefono_contacto',
        'telefono_contacto_2',
        'email_contacto',
        'email_contacto_2',
        'dia_vencimiento',
        'activo',
        'acepto_reglamento',
        'acepto_reglamento_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'genero' => Genero::class,
            'grupo_etario' => GrupoEtario::class,
            'nivel' => NivelEntrenamiento::class,
            'dia_vencimiento' => 'integer',
            'activo' => 'boolean',
            'acepto_reglamento' => 'boolean',
            'acepto_reglamento_at' => 'datetime',
        ];
    }

    /**
     * El nivel del alumno se deriva SIEMPRE de su cinturón (grado): al guardar
     * se recalcula, así que graduar en un examen (que cambia grado_id) actualiza
     * el nivel solo, y un alumno nuevo/sin cinturón queda en Principiantes.
     */
    protected static function booted(): void
    {
        static::saving(function (Estudiante $estudiante): void {
            $estudiante->nivel = $estudiante->grado_id
                ? (Grado::find($estudiante->grado_id)?->nivelEntrenamiento() ?? NivelEntrenamiento::Principiantes)
                : NivelEntrenamiento::Principiantes;
        });
    }

    /**
     * Grupo dueña de la ficha.
     *
     * @return BelongsTo<Grupo, $this>
     */
    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class);
    }

    /**
     * Cuenta de login del propio alumno (si tiene).
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Sede a la que asiste.
     *
     * @return BelongsTo<Sede, $this>
     */
    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class);
    }

    /**
     * Instructor a cargo (registrado en la inscripción).
     *
     * @return BelongsTo<User, $this>
     */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    /**
     * Grado (cinturón) actual.
     *
     * @return BelongsTo<Grado, $this>
     */
    public function grado(): BelongsTo
    {
        return $this->belongsTo(Grado::class);
    }

    /**
     * Apoderados (Users) del estudiante.
     *
     * @return BelongsToMany<User, $this>
     */
    public function apoderados(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'apoderado_estudiante');
    }

    /**
     * Programas en los que está inscrito.
     *
     * @return BelongsToMany<Programa, $this>
     */
    public function programas(): BelongsToMany
    {
        return $this->belongsToMany(Programa::class, 'estudiante_programa');
    }

    /**
     * Asistencias del alumno, a través de su matrícula (la asistencia se registra
     * por matrícula desde el rediseño Fase 4). Puente transitorio mientras la
     * ficha Estudiante sigue viva.
     *
     * @return HasManyThrough<Asistencia, Matricula, $this>
     */
    public function asistencias(): HasManyThrough
    {
        return $this->hasManyThrough(
            Asistencia::class,
            Matricula::class,
            'estudiante_id', // FK en matriculas → estudiantes
            'matricula_id',  // FK en asistencias → matriculas
            'id',
            'id',
        );
    }

    /**
     * Pagos del alumno, a través de su matrícula (los pagos van por matrícula
     * desde el rediseño Fase 4). Puente transitorio mientras vive Estudiante.
     *
     * @return HasManyThrough<Pago, Matricula, $this>
     */
    public function pagos(): HasManyThrough
    {
        return $this->hasManyThrough(
            Pago::class,
            Matricula::class,
            'estudiante_id', // FK en matriculas → estudiantes
            'matricula_id',  // FK en pagos → matriculas
            'id',
            'id',
        );
    }

    /**
     * Inscripciones a convocatorias de examen.
     *
     * @return HasMany<Inscripcion, $this>
     */
    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class);
    }

    /**
     * Historial de graduaciones.
     *
     * @return HasMany<Graduacion, $this>
     */
    public function graduaciones(): HasMany
    {
        return $this->hasMany(Graduacion::class);
    }

    /**
     * Logros (recompensas ganadas) del alumno.
     *
     * @return HasMany<Logro, $this>
     */
    public function logros(): HasMany
    {
        return $this->hasMany(Logro::class);
    }

    /**
     * Recompensas ganadas (a través de los logros).
     *
     * @return BelongsToMany<Recompensa, $this>
     */
    public function recompensas(): BelongsToMany
    {
        return $this->belongsToMany(Recompensa::class, 'logros')
            ->withPivot(['otorgado_at', 'otorgado_por', 'nota'])
            ->withTimestamps();
    }

    /**
     * Escala de grados (cinturones) que corresponde según el grupo etario.
     */
    public function escalaGrado(): EscalaGrado
    {
        return EscalaGrado::paraGrupo($this->grupo_etario);
    }

    /**
     * Edad en años (null si no hay fecha de nacimiento).
     */
    public function edad(): ?int
    {
        return $this->fecha_nacimiento?->age;
    }

    /**
     * Solo estudiantes activos.
     *
     * @param  Builder<Estudiante>  $query
     * @return Builder<Estudiante>
     */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    /**
     * ¿El estudiante corresponde a alguna de las clases del instructor dado?
     *
     * No hay inscripción explícita alumno↔clase: las clases agrupan por sede y
     * grupo etario, así que un alumno "pertenece" a un instructor cuando su sede
     * y grupo etario coinciden con alguna clase donde el instructor está
     * asignado (con cualquier papel).
     */
    public function esDeInstructor(User $instructor): bool
    {
        return $instructor->clases()
            ->where('sede_id', $this->sede_id)
            ->where('grupo_etario', $this->grupo_etario?->value)
            ->exists();
    }

    /**
     * Limita la consulta a los estudiantes visibles para el usuario dado. La
     * misma regla de visibilidad que aplica la EstudiantePolicy, para no
     * duplicar la lógica entre el listado y la autorización por ficha:
     *
     * - Con "gestionar alumnos" (dirección, administrativo, federación,
     *   admin-plataforma): todos los de su grupo (ya acotada por el tenant).
     * - Instructor: solo los de las clases donde está asignado.
     * - Apoderado: solo sus hijos.
     * - Cualquier otro: ninguno.
     *
     * @param  Builder<Estudiante>  $query
     * @return Builder<Estudiante>
     */
    public function scopeVisiblePara(Builder $query, User $usuario): Builder
    {
        if ($usuario->can('gestionar alumnos')) {
            return $query;
        }

        if ($usuario->hasRole('instructor')) {
            $clases = $usuario->clases()->get(['sede_id', 'grupo_etario']);

            if ($clases->isEmpty()) {
                return $query->whereRaw('1 = 0');
            }

            return $query->where(function (Builder $q) use ($clases): void {
                foreach ($clases as $clase) {
                    $q->orWhere(fn (Builder $sub) => $sub
                        ->where('sede_id', $clase->sede_id)
                        ->where('grupo_etario', $clase->grupo_etario->value));
                }
            });
        }

        if ($usuario->hasRole('apoderado')) {
            return $query->whereHas('apoderados', fn (Builder $q) => $q->whereKey($usuario->id));
        }

        return $query->whereRaw('1 = 0');
    }
}
