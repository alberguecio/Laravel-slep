<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // CORS Configuration
        $middleware->api([
            \App\Http\Middleware\HandleCors::class,
        ]);
        
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
        
        // JWT Middleware
        $middleware->alias([
            'jwt.auth' => \App\Http\Middleware\JWTAuthMiddleware::class,
            'check.auth' => \App\Http\Middleware\CheckAuth::class,
        ]);
        
        // Aplicar middleware de autenticación a todas las rutas web excepto login
        $middleware->web(append: [
            \App\Http\Middleware\CheckAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

