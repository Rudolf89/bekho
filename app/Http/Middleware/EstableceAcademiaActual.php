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
 * Debe correr después de la autenticación. El admin-plataforma no está atado a una
 * academia: elige cuál gestionar mediante el selector (se guarda en la sesión).
 * Por defecto toma la primera, para que siempre haya un tenant activo y los
 * formularios puedan crear registros sin fallar.
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

            if ($usuario->hasRole('admin-plataforma') || $usuario->hasRole('federacion')) {
                // El admin-plataforma y la federación ven TODO el sistema. La academia
                // activa solo sirve como contexto para crear registros (no filtra
                // lecturas). La federación además es de solo lectura (ver User).
                Academia::set($this->academiaActivaSuperAdmin($request), filtraLecturas: false);
            } else {
                Academia::set($usuario->academia_id);
            }
        }

        return $next($request);
    }

    /**
     * Academia activa elegida por el admin-plataforma (de la sesión). Si no hay una
     * elegida, usa la primera academia y la deja fijada.
     */
    protected function academiaActivaSuperAdmin(Request $request): ?int
    {
        $academiaId = $request->session()->get('academia_activa_id');

        // Se valida que siga existiendo (pudo eliminarse la academia elegida).
        if ($academiaId && ! AcademiaModel::whereKey($academiaId)->exists()) {
            $academiaId = null;
        }

        if (! $academiaId) {
            $academiaId = AcademiaModel::query()->orderBy('nombre')->value('id');

            if ($academiaId) {
                $request->session()->put('academia_activa_id', $academiaId);
            }
        }

        return $academiaId;
    }
}
