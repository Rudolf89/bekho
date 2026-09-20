<?php

namespace App\Models;

use App\Enums\Cuadrante;
use App\Enums\GrupoEtario;
use App\Enums\HabilidadVida;
use App\Enums\NivelEntrenamiento;
use App\Enums\TipoBloque;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Planificación de clase: rutina de una clase (grupo etario × nivel × programa).
 * Combina bloques de actividad, la capa de Cuadrantes de Enseñanza y una
 * Habilidad para la Vida. ("Planilla" queda reservado para competencia.)
 *
 * Es TRANSVERSAL: el currículo/rutina es contenido ATA/BEKHO compartido por toda
 * la federación (sin grupo_id), como cargos_rangos.
 */
class PlanificacionClase extends Model
{
    /**
     * @var string
     */
    protected $table = 'planificaciones_clase';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'programa_id',
        'nombre',
        'grupo_etario',
        'nivel',
        'habilidad_vida',
        'activo',
        'fuente',
        'verificado',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'grupo_etario' => GrupoEtario::class,
            'nivel' => NivelEntrenamiento::class,
            'habilidad_vida' => HabilidadVida::class,
            'activo' => 'boolean',
            'verificado' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Programa, $this>
     */
    public function programa(): BelongsTo
    {
        return $this->belongsTo(Programa::class);
    }

    /**
     * Bloques de actividad, ordenados.
     *
     * @return HasMany<BloquePlanificacion, $this>
     */
    public function bloques(): HasMany
    {
        return $this->hasMany(BloquePlanificacion::class)->orderBy('orden');
    }

    /**
     * Cuadrantes de enseñanza.
     *
     * @return HasMany<CuadrantePlanificacion, $this>
     */
    public function cuadrantes(): HasMany
    {
        return $this->hasMany(CuadrantePlanificacion::class);
    }

    /**
     * Clases del horario que usan esta planificación.
     *
     * @return HasMany<Clase, $this>
     */
    public function clases(): HasMany
    {
        return $this->hasMany(Clase::class, 'planificacion_clase_id');
    }

    /**
     * Currículo técnico del nivel de la planificación (fórmula, patadas, etc.).
     *
     * @return BelongsTo<CurriculoNivel, $this>
     */
    public function curriculo(): BelongsTo
    {
        return $this->belongsTo(CurriculoNivel::class, 'nivel', 'nivel');
    }

    /**
     * Crea los bloques (los 9 estándar, en orden) y los cuadrantes (los 4) vacíos
     * para que el instructor solo complete el contenido.
     */
    public function generarEstructura(): void
    {
        foreach (TipoBloque::cases() as $orden => $tipo) {
            $this->bloques()->create([
                'tipo' => $tipo,
                'orden' => $orden + 1,
            ]);
        }

        foreach (Cuadrante::cases() as $cuadrante) {
            $this->cuadrantes()->create([
                'cuadrante' => $cuadrante,
            ]);
        }
    }
}
