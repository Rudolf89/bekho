<?php

namespace App\Models;

use App\Enums\GrupoEtario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Bloque de semanas del planificador de Cinturón Negro (con un tema). Catálogo
 * compartido (sin grupo_id).
 */
class PlanificacionCinturonNegro extends Model
{
    protected $table = 'planificaciones_cinturon_negro';

    /**
     * @var list<string>
     */
    protected $fillable = ['clave', 'label', 'tema', 'icono', 'color', 'orden', 'fuente', 'verificado'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['verificado' => 'boolean'];
    }

    /**
     * @return HasMany<SeccionCinturonNegro, $this>
     */
    public function secciones(): HasMany
    {
        return $this->hasMany(SeccionCinturonNegro::class, 'planificacion_cinturon_negro_id')->orderBy('orden');
    }

    /**
     * @return HasMany<AdaptacionCinturonNegro, $this>
     */
    public function adaptaciones(): HasMany
    {
        return $this->hasMany(AdaptacionCinturonNegro::class, 'planificacion_cinturon_negro_id');
    }

    /**
     * Ítems de una sección concreta (warmup_general, sparring, …).
     *
     * @return list<string>
     */
    public function itemsDe(string $seccion): array
    {
        return array_values(
            $this->secciones
                ->where('seccion', $seccion)
                ->map(fn (SeccionCinturonNegro $s) => $s->item)
                ->all()
        );
    }

    /**
     * Adaptación para un grupo etario.
     */
    public function adaptacionDe(GrupoEtario|string $grupo): ?string
    {
        $valor = $grupo instanceof GrupoEtario ? $grupo->value : $grupo;

        return $this->adaptaciones->firstWhere('grupo_etario', $valor)?->texto;
    }

    /**
     * @param  Builder<PlanificacionCinturonNegro>  $query
     * @return Builder<PlanificacionCinturonNegro>
     */
    public function scopeOrdenadas(Builder $query): Builder
    {
        return $query->orderBy('orden');
    }
}
