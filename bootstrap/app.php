<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'mobile' => \App\Http\Middleware\MobileAuth::class,
        ]);
        $middleware->web(prepend: [\App\Http\Middleware\SchemaReady::class], append: [
            \App\Http\Middleware\SessionExpiry::class,
            \App\Http\Middleware\RateLimitRequests::class,
        ]);
        $middleware->validateCsrfTokens(except: ['api/m/*', 'oauth/backchannel-logout']);
        $middleware->redirectGuestsTo('/login');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn ($request) => $request->is('api/m/*') || $request->expectsJson());
    })->create();
