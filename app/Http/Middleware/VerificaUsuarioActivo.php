<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cierra la sesión de un usuario que fue desactivado mientras estaba dentro.
 *
 * El bloqueo en el inicio de sesión evita que una cuenta inactiva entre, pero
 * si se desactiva a alguien que ya tiene sesión abierta, este middleware lo
 * expulsa en su siguiente petición para que "desactivar" signifique de verdad
 * "quitar el acceso".
 */
class VerificaUsuarioActivo
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        // Solo se expulsa cuando 'activo' es explícitamente false (el guard
        // carga el registro completo desde la BD). Un null significa "no
        // cargado" y no debe interpretarse como cuenta desactivada.
        if ($usuario && $usuario->activo === false) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => __('Tu cuenta fue desactivada. Contacta al administrador de la escuela.'),
            ]);
        }

        return $next($request);
    }
}
