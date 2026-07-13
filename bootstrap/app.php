<?php

use App\Http\Middleware\EstableceAcademiaActual;
use App\Http\Middleware\ExigeDosFactores;
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
        // Corren después de autenticar: primero se fija la academia (tenant)
        // activa y luego se exige 2FA a los roles que la requieren.
        $middleware->web(append: [
            EstableceAcademiaActual::class,
            ExigeDosFactores::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
