<?php

use App\Http\Middleware\EstableceGrupoActual;
use App\Http\Middleware\ExigeDosFactores;
use App\Http\Middleware\VerificaUsuarioActivo;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

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
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
