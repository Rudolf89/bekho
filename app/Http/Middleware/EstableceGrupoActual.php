<?php

namespace App\Http\Middleware;

use App\Models\Grupo as GrupoModel;
use App\Support\Tenancy\Grupo;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fija el grupo (tenant) activo a partir del usuario autenticado.
 *
 * Debe correr después de la autenticación.
 *
 * El admin-plataforma no está atado a un grupo: elige en el selector cuál
 * ver. Si elige una, la vista se ACOTA a ese grupo (filtra lecturas y es el
 * contexto de creación). Si no elige ninguna ("Todos los grupos"), ve todo el
 * sistema y la primera grupo queda solo como contexto para crear.
 *
 * La federación supervisa todos los grupos (solo lectura), siempre en modo
 * "ver todo".
 */
class EstableceGrupoActual
{
    /**
     * Maneja la petición entrante.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $usuario = Auth::user();

            if ($usuario->hasRole('admin-plataforma')) {
                $elegida = $this->grupoElegida($request);

                if ($elegida !== null) {
                    // Grupo enfocada: la vista se acota a ella.
                    Grupo::set($elegida, filtraLecturas: true);
                } else {
                    // "Todos los grupos": ve todo; contexto de creación = la primera.
                    Grupo::set($this->primeraGrupo(), filtraLecturas: false);
                }
            } elseif ($usuario->hasRole('federacion')) {
                Grupo::set($this->primeraGrupo(), filtraLecturas: false);
            } else {
                Grupo::set($usuario->grupo_id);
            }
        }

        return $next($request);
    }

    /**
     * Grupo elegida por el admin-plataforma en el selector (de la sesión), o
     * null si eligió "Todas" o la elegida ya no existe.
     */
    protected function grupoElegida(Request $request): ?int
    {
        $grupoId = $request->session()->get('grupo_activa_id');

        if (! $grupoId || ! GrupoModel::whereKey($grupoId)->exists()) {
            return null;
        }

        return (int) $grupoId;
    }

    /**
     * Primera grupo (por nombre), como contexto de creación en modo "Todas".
     */
    protected function primeraGrupo(): ?int
    {
        return GrupoModel::query()->orderBy('nombre')->value('id');
    }
}
