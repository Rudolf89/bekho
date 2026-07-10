<?php

namespace App\Models\Concerns;

use App\Support\Tenancy\Academia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Da a un modelo aislamiento por academia (tenancy).
 *
 * Añade un global scope "academia" que filtra por la academia activa y rellena
 * automáticamente academia_id al crear registros. El scope sinAcademia() permite
 * saltarse el aislamiento cuando se necesita (p. ej. tareas administrativas).
 */
trait PerteneceAcademia
{
    /**
     * Arranca el trait: registra el global scope y el relleno automático.
     */
    public static function bootPerteneceAcademia(): void
    {
        static::addGlobalScope('academia', function (Builder $builder): void {
            if (Academia::hayActiva()) {
                $modelo = $builder->getModel();
                $builder->where($modelo->getTable().'.academia_id', Academia::id());
            }
        });

        static::creating(function (Model $modelo): void {
            if (empty($modelo->academia_id) && Academia::hayActiva()) {
                $modelo->academia_id = Academia::id();
            }
        });
    }

    /**
     * Consulta sin el aislamiento por academia.
     */
    public function scopeSinAcademia(Builder $query): Builder
    {
        return $query->withoutGlobalScope('academia');
    }
}
