<?php

namespace App\Models;

use App\Enums\EscalaGrado;
use App\Enums\GrupoEtario;
use App\Enums\NivelEntrenamiento;
use App\Models\Concerns\PerteneceAcademia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Ficha del alumno. Distinta del User: el estudiante puede o no tener cuenta.
 */
class Estudiante extends Model
{
    use PerteneceAcademia;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'academia_id',
        'user_id',
        'sede_id',
        'grado_id',
        'nombre',
        'rut',
        'fecha_nacimiento',
        'grupo_etario',
        'nivel',
        'telefono_contacto',
        'email_contacto',
        'activo',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'grupo_etario' => GrupoEtario::class,
            'nivel' => NivelEntrenamiento::class,
            'activo' => 'boolean',
        ];
    }

    /**
     * Academia dueña de la ficha.
     *
     * @return BelongsTo<Academia, $this>
     */
    public function academia(): BelongsTo
    {
        return $this->belongsTo(Academia::class);
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
     * Asistencias registradas.
     *
     * @return HasMany<Asistencia, $this>
     */
    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class);
    }

    /**
     * Pagos registrados.
     *
     * @return HasMany<Pago, $this>
     */
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
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
}
