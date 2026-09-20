<?php

use App\Http\Middleware\EstableceGrupoActual;
use App\Http\Middleware\ExigeDosFactores;
use App\Http\Middleware\VerificaUsuarioActivo;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Contracts\Session\Middleware\AuthenticatesSessions;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Corren después de autenticar: primero se expulsa a los usuarios
        // desactivados, luego se fija el grupo (tenant) activa y por último
        // se exige 2FA a los roles que la requieren.
        $middleware->web(append: [
            VerificaUsuarioActivo::class,
            EstableceGrupoActual::class,
            ExigeDosFactores::class,
        ]);

        // El grupo (tenant) debe fijarse ANTES de resolver los route-model
        // bindings: así el binding de un modelo con aislamiento por grupo se
        // acota al grupo activo (y, al fallar cerrado, no permite abrir por URL
        // registros de otro grupo). Por eso EstableceGrupoActual va antes de
        // SubstituteBindings en la prioridad.
        $middleware->priority([
            HandlePrecognitiveRequests::class,
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            AuthenticatesRequests::class,
            VerificaUsuarioActivo::class,
            EstableceGrupoActual::class,
            ExigeDosFactores::class,
            ThrottleRequests::class,
            ThrottleRequestsWithRedis::class,
            AuthenticatesSessions::class,
            SubstituteBindings::class,
            Authorize::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
