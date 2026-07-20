<?php

namespace App\Models;

use App\Enums\Cuadrante;
use App\Enums\GrupoEtario;
use App\Enums\HabilidadVida;
use App\Enums\NivelEntrenamiento;
use App\Enums\TipoBloque;
use App\Models\Concerns\PerteneceAcademia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Planilla: rutina de una clase (grupo etario × nivel × programa). Combina
 * bloques de actividad, la capa de Cuadrantes de Enseñanza y una Habilidad para
 * la Vida.
 */
class Planilla extends Model
{
    use PerteneceAcademia;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'academia_id',
        'programa_id',
        'nombre',
        'grupo_etario',
        'nivel',
        'habilidad_vida',
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
            'habilidad_vida' => HabilidadVida::class,
            'activo' => 'boolean',
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
     * @return HasMany<BloquePlanilla, $this>
     */
    public function bloques(): HasMany
    {
        return $this->hasMany(BloquePlanilla::class)->orderBy('orden');
    }

    /**
     * Cuadrantes de enseñanza.
     *
     * @return HasMany<CuadrantePlanilla, $this>
     */
    public function cuadrantes(): HasMany
    {
        return $this->hasMany(CuadrantePlanilla::class);
    }

    /**
     * Clases del horario que usan esta planilla.
     *
     * @return HasMany<Clase, $this>
     */
    public function clases(): HasMany
    {
        return $this->hasMany(Clase::class);
    }

    /**
     * Ejercicios de calentamiento guardados para esta planilla (rutina armada).
     *
     * @return BelongsToMany<EjercicioCalentamiento, $this>
     */
    public function calentamiento(): BelongsToMany
    {
        return $this->belongsToMany(
            EjercicioCalentamiento::class,
            'calentamiento_planilla',
            'planilla_id',
            'ejercicio_calentamiento_id',
        )->withPivot('orden')->orderBy('pivot_orden')->withTimestamps();
    }

    /**
     * Currículo técnico del nivel de la planilla (fórmula, patadas, etc.).
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
                'academia_id' => $this->academia_id,
                'tipo' => $tipo,
                'orden' => $orden + 1,
            ]);
        }

        foreach (Cuadrante::cases() as $cuadrante) {
            $this->cuadrantes()->create([
                'academia_id' => $this->academia_id,
                'cuadrante' => $cuadrante,
            ]);
        }
    }
}
