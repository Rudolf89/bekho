<?php

namespace App\Http\Middleware;

use App\Models\Academia as AcademiaModel;
use App\Support\Tenancy\Academia;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fija la academia (tenant) activa a partir del usuario autenticado.
 *
 * Debe correr después de la autenticación.
 *
 * El admin-plataforma no está atado a una academia: elige en el selector cuál
 * ver. Si elige una, la vista se ACOTA a esa academia (filtra lecturas y es el
 * contexto de creación). Si no elige ninguna ("Todas las academias"), ve todo el
 * sistema y la primera academia queda solo como contexto para crear.
 *
 * La federación supervisa todas las academias (solo lectura), siempre en modo
 * "ver todo".
 */
class EstableceAcademiaActual
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
                $elegida = $this->academiaElegida($request);

                if ($elegida !== null) {
                    // Academia enfocada: la vista se acota a ella.
                    Academia::set($elegida, filtraLecturas: true);
                } else {
                    // "Todas las academias": ve todo; contexto de creación = la primera.
                    Academia::set($this->primeraAcademia(), filtraLecturas: false);
                }
            } elseif ($usuario->hasRole('federacion')) {
                Academia::set($this->primeraAcademia(), filtraLecturas: false);
            } else {
                Academia::set($usuario->academia_id);
            }
        }

        return $next($request);
    }

    /**
     * Academia elegida por el admin-plataforma en el selector (de la sesión), o
     * null si eligió "Todas" o la elegida ya no existe.
     */
    protected function academiaElegida(Request $request): ?int
    {
        $academiaId = $request->session()->get('academia_activa_id');

        if (! $academiaId || ! AcademiaModel::whereKey($academiaId)->exists()) {
            return null;
        }

        return (int) $academiaId;
    }

    /**
     * Primera academia (por nombre), como contexto de creación en modo "Todas".
     */
    protected function primeraAcademia(): ?int
    {
        return AcademiaModel::query()->orderBy('nombre')->value('id');
    }
}
