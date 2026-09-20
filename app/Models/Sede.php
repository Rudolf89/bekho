<?php

namespace App\Models;

use App\Enums\TipoSede;
use App\Models\Concerns\PerteneceGrupo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sede extends Model
{
    use PerteneceGrupo;

    /**
     * Atributos asignables masivamente.
     *
     * @var list<string>
     */
    protected $fillable = [
        'grupo_id',
        'responsable_persona_id',
        'nombre',
        'direccion',
        'comuna',
        'region',
        'capacidad',
        'descuento_semestral_pct',
        'descuento_anual_pct',
        'clases_gracia_morosidad',
        'exencion_matricula_desde_mes',
        'exencion_matricula_hasta_mes',
        'dia_vencimiento_maximo',
        'tipo',
        'privada',
        'activo',
    ];

    /**
     * Casts de atributos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoSede::class,
            'capacidad' => 'integer',
            'descuento_semestral_pct' => 'integer',
            'descuento_anual_pct' => 'integer',
            'clases_gracia_morosidad' => 'integer',
            'exencion_matricula_desde_mes' => 'integer',
            'exencion_matricula_hasta_mes' => 'integer',
            'dia_vencimiento_maximo' => 'integer',
            'privada' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    /**
     * Parámetro de cobro con respaldo de la federación: usa el valor de la sede si
     * está definido; si no, el de su federación; y en último caso el default dado.
     */
    public function parametroCobro(string $columna, int $default): int
    {
        if ($this->{$columna} !== null) {
            return (int) $this->{$columna};
        }

        $valorFederacion = $this->grupo?->federacion?->{$columna};

        return $valorFederacion !== null ? (int) $valorFederacion : $default;
    }

    /**
     * Clases de gracia tras el vencimiento antes de bloquear por deuda.
     */
    public function clasesGraciaMorosidad(): int
    {
        return $this->parametroCobro('clases_gracia_morosidad', 3);
    }

    /**
     * Mes (1–12) en que empieza la ventana de exención de matrícula (año anterior).
     */
    public function exencionMatriculaDesdeMes(): int
    {
        return $this->parametroCobro('exencion_matricula_desde_mes', 10);
    }

    /**
     * Mes (1–12) en que termina la ventana de exención de matrícula (año objetivo).
     */
    public function exencionMatriculaHastaMes(): int
    {
        return $this->parametroCobro('exencion_matricula_hasta_mes', 1);
    }

    /**
     * Día máximo permitido para el vencimiento de la mensualidad.
     */
    public function diaVencimientoMaximo(): int
    {
        return $this->parametroCobro('dia_vencimiento_maximo', 20);
    }

    /**
     * Grupo dueña de la sede.
     *
     * @return BelongsTo<Grupo, $this>
     */
    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class);
    }

    /**
     * Persona responsable de la sede: origen del crédito de graduación cuando la
     * matrícula del alumno no tiene instructor asignado (regla de origen 2).
     *
     * @return BelongsTo<Persona, $this>
     */
    public function responsable(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'responsable_persona_id');
    }

    /**
     * Instructores asignados a la sede.
     *
     * @return BelongsToMany<User, $this>
     */
    public function instructores(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'sede_user');
    }

    /**
     * Tarifas de cobro de la sede (matrícula, mensualidad, … por tramo familiar).
     *
     * @return HasMany<TarifaSede, $this>
     */
    public function tarifas(): HasMany
    {
        return $this->hasMany(TarifaSede::class);
    }
}
