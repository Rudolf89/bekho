<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige 2FA confirmada a los usuarios cuyos roles la requieren.
 *
 * Si un usuario con un rol de la lista config('bekho.2fa_obligatorio_para') no
 * tiene su 2FA confirmada, se le redirige a la pantalla de seguridad para que
 * la active. Las rutas necesarias para activarla (la propia pantalla, el
 * logout, la confirmación de contraseña, los endpoints de 2FA y las peticiones
 * de Livewire) quedan exentas para evitar bucles de redirección.
 */
class ExigeDosFactores
{
    /**
     * Nombres de ruta exentos de la exigencia de 2FA.
     *
     * @var list<string>
     */
    protected array $rutasExentas = [
        'security.edit',
        'password.confirm',
        'password.confirmation',
        'well-known.passkeys',
    ];

    /**
     * Prefijos de nombre de ruta exentos (p. ej. todas las de two-factor.*).
     *
     * @var list<string>
     */
    protected array $prefijosExentos = [
        'two-factor.',
    ];

    /**
     * Maneja la petición entrante.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = Auth::user();

        // Solo se intercepta la navegación (GET de páginas). Las peticiones de
        // Livewire y los POST (incluidos los endpoints que activan la 2FA) pasan,
        // para no romper el propio flujo de activación ni caer en bucles.
        if ($usuario
            && $request->isMethod('GET')
            && ! $request->expectsJson()
            && $this->exigeDosFactores($usuario)
            && ! $this->tieneDosFactoresConfirmada($usuario)
            && ! $this->rutaExenta($request)
        ) {
            return redirect()->route('security.edit')->with(
                'status',
                'Tu rol requiere autenticación de dos factores. Actívala para continuar.',
            );
        }

        return $next($request);
    }

    /**
     * Indica si el usuario tiene un rol que exige 2FA.
     */
    protected function exigeDosFactores(mixed $usuario): bool
    {
        $rolesObligados = config('bekho.2fa_obligatorio_para', []);

        return $usuario->hasAnyRole($rolesObligados);
    }

    /**
     * Indica si el usuario ya confirmó su 2FA.
     */
    protected function tieneDosFactoresConfirmada(mixed $usuario): bool
    {
        return ! is_null($usuario->two_factor_confirmed_at);
    }

    /**
     * Indica si la ruta actual está exenta de la exigencia.
     */
    protected function rutaExenta(Request $request): bool
    {
        $nombre = $request->route()?->getName();

        if ($nombre === null) {
            return false;
        }

        if (in_array($nombre, $this->rutasExentas, true)) {
            return true;
        }

        return Str::startsWith($nombre, $this->prefijosExentos);
    }
}
