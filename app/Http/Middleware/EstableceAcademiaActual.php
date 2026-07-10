<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\Academia;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fija la academia (tenant) activa a partir del usuario autenticado.
 *
 * Debe correr después de la autenticación. Un usuario con rol super-admin ve
 * todas las academias (no se fija ninguna).
 */
class EstableceAcademiaActual
{
    /**
     * Maneja la petición entrante.
     *
     * @param  \Closure(\Illuminate\Http\Request): \Symfony\Component\HttpFoundation\Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $usuario = Auth::user();

            if ($usuario->hasRole('super-admin')) {
                Academia::set(null);
            } else {
                Academia::set($usuario->academia_id);
            }
        }

        return $next($request);
    }
}
