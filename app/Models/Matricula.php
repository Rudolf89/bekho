<?php

namespace App\Models;

use App\Enums\EscalaGrado;
use App\Enums\EstadoMatricula;
use App\Enums\GrupoEtario;
use App\Enums\NivelEntrenamiento;
use App\Models\Concerns\PerteneceGrupo;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'grupo_etario',
        'nivel',
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
            'grupo_etario' => GrupoEtario::class,
            'nivel' => NivelEntrenamiento::class,
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
     * Asistencias registradas para esta matrícula.
     *
     * @return HasMany<Asistencia, $this>
     */
    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class);
    }

    /**
     * Pagos registrados para esta matrícula.
     *
     * @return HasMany<Pago, $this>
     */
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }

    /**
     * Logros (gamificación) ganados por esta matrícula.
     *
     * @return HasMany<Logro, $this>
     */
    public function logros(): HasMany
    {
        return $this->hasMany(Logro::class);
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
     * Historial de graduaciones de esta matrícula.
     *
     * @return HasMany<Graduacion, $this>
     */
    public function graduaciones(): HasMany
    {
        return $this->hasMany(Graduacion::class);
    }

    /**
     * Suspensiones (congelamientos) de esta matrícula.
     *
     * @return HasMany<Suspension, $this>
     */
    public function suspensiones(): HasMany
    {
        return $this->hasMany(Suspension::class);
    }

    /**
     * ¿La matrícula está suspendida en el período (mes) dado?
     */
    public function estaSuspendidaEn(CarbonInterface $periodo): bool
    {
        return $this->suspensiones()->cubrePeriodo($periodo)->exists();
    }

    /**
     * Escala de grados que corresponde al grupo etario de la matrícula.
     */
    public function escalaGrado(): EscalaGrado
    {
        return EscalaGrado::paraGrupo($this->grupo_etario);
    }

    /**
     * ¿La matrícula pertenece a una clase del instructor (misma sede y grupo etario)?
     */
    public function esDeInstructor(User $instructor): bool
    {
        return $instructor->clases()
            ->where('sede_id', $this->sede_id)
            ->where('grupo_etario', $this->grupo_etario?->value)
            ->exists();
    }

    /**
     * Acota las matrículas a las visibles para el usuario: dirección/administración
     * ve todas; el instructor solo las de sus clases (misma sede y grupo etario);
     * el apoderado solo las de las personas que tutela; el resto, ninguna.
     *
     * @param  Builder<Matricula>  $query
     * @return Builder<Matricula>
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
            if (! $usuario->persona_id) {
                return $query->whereRaw('1 = 0');
            }

            return $query->whereIn('persona_id', Tutela::query()
                ->where('apoderado_persona_id', $usuario->persona_id)
                ->pluck('alumno_persona_id'));
        }

        return $query->whereRaw('1 = 0');
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
