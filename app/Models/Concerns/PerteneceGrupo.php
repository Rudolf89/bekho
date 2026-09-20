<?php

namespace App\Models\Concerns;

use App\Support\Tenancy\Grupo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Da a un modelo aislamiento por grupo (tenancy). FALLA CERRADO.
 *
 * Añade un global scope "grupo" que filtra por el grupo activo y rellena
 * automáticamente grupo_id al crear registros. Tres situaciones:
 *  1. Grupo activo con filtro → filtra por grupo_id.
 *  2. Grupo activo sin filtro (admin-plataforma) → ve todos los grupos.
 *  3. SIN grupo activo y SIN modo sistema → NO devuelve nada (whereRaw 1=0). Un
 *     job, comando o test mal montado no puede ver todos los grupos por accidente.
 *
 * Para el código de sistema que SÍ debe ver todos los grupos, usar
 * `Grupo::comoSistema(fn () => ...)`. El scope `sinGrupo()` sigue disponible para
 * consultas puntuales que se saltan el aislamiento a propósito.
 */
trait PerteneceGrupo
{
    /**
     * Tablas ya avisadas en consola (para no inundar el log con el mismo aviso).
     *
     * @var array<string, true>
     */
    protected static array $avisadasSinGrupo = [];

    /**
     * Arranca el trait: registra el global scope y el relleno automático.
     */
    public static function bootPerteneceGrupo(): void
    {
        static::addGlobalScope('grupo', function (Builder $builder): void {
            // Modo sistema: aislamiento desactivado a propósito (ve todos los grupos).
            if (Grupo::esSistema()) {
                return;
            }

            if (Grupo::hayActiva()) {
                // El admin-plataforma ve todo: hay grupo activo (para crear) pero no
                // se filtran las lecturas.
                if (Grupo::filtraLecturas()) {
                    $modelo = $builder->getModel();
                    $builder->where($modelo->getTable().'.grupo_id', Grupo::id());
                }

                return;
            }

            // Sin grupo activo y sin modo sistema: falla cerrado (no devuelve nada),
            // salvo los modelos de identidad que se resuelven antes del tenant.
            if (! $builder->getModel()->fallaCerradoSinGrupo()) {
                return;
            }

            $builder->whereRaw('1 = 0');
            static::avisarSinGrupo($builder->getModel()->getTable());
        });

        static::creating(function (Model $modelo): void {
            if (empty($modelo->grupo_id) && Grupo::hayActiva()) {
                $modelo->grupo_id = Grupo::id();
            }
        });
    }

    /**
     * En consola, avisa (una vez por tabla) cuando una consulta cae en el filtro
     * vacío por no haber grupo activo: así un comando mal montado se nota en vez de
     * devolver cero en silencio. Si necesita ver todos los grupos, debe envolver la
     * operación en Grupo::comoSistema().
     */
    protected static function avisarSinGrupo(string $tabla): void
    {
        if (! app()->runningInConsole() || isset(static::$avisadasSinGrupo[$tabla])) {
            return;
        }

        static::$avisadasSinGrupo[$tabla] = true;

        Log::warning(
            "Consulta a «{$tabla}» sin grupo activo: se filtró a vacío (tenancy falla cerrado). ".
            'Si es código de sistema que debe ver todos los grupos, envuélvelo en Grupo::comoSistema().'
        );
    }

    /**
     * ¿Este modelo debe fallar cerrado (no devolver nada) cuando no hay grupo
     * activo ni modo sistema? Por defecto sí. Los modelos de identidad que se
     * resuelven ANTES de que exista un tenant (p. ej. la cuenta de usuario en la
     * autenticación) lo sobrescriben devolviendo false.
     */
    public function fallaCerradoSinGrupo(): bool
    {
        return true;
    }

    /**
     * Consulta sin el aislamiento por grupo.
     */
    public function scopeSinGrupo(Builder $query): Builder
    {
        return $query->withoutGlobalScope('grupo');
    }
}
