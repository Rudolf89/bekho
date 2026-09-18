<?php

namespace App\Models\Concerns;

use App\Support\Tenancy\Grupo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Da a un modelo aislamiento por grupo (tenancy).
 *
 * Añade un global scope "grupo" que filtra por el grupo activo y rellena
 * automáticamente grupo_id al crear registros. El scope sinGrupo() permite
 * saltarse el aislamiento cuando se necesita (p. ej. tareas administrativas).
 */
trait PerteneceGrupo
{
    /**
     * Arranca el trait: registra el global scope y el relleno automático.
     */
    public static function bootPerteneceGrupo(): void
    {
        static::addGlobalScope('grupo', function (Builder $builder): void {
            // El admin-plataforma ve todo: hay grupo activo (para crear) pero no
            // se filtran las lecturas.
            if (Grupo::hayActiva() && Grupo::filtraLecturas()) {
                $modelo = $builder->getModel();
                $builder->where($modelo->getTable().'.grupo_id', Grupo::id());
            }
        });

        static::creating(function (Model $modelo): void {
            if (empty($modelo->grupo_id) && Grupo::hayActiva()) {
                $modelo->grupo_id = Grupo::id();
            }
        });
    }

    /**
     * Consulta sin el aislamiento por grupo.
     */
    public function scopeSinGrupo(Builder $query): Builder
    {
        return $query->withoutGlobalScope('grupo');
    }
}
