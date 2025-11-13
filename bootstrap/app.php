<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Register broadcasting auth with Sanctum middleware
            Route::middleware(['api', 'auth:sanctum'])
                ->post('/broadcasting/auth', [\Illuminate\Broadcasting\BroadcastController::class, 'authenticate']);

            // Register broadcast channels
            require base_path('routes/channels.php');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Optional: Add custom logging for specific exceptions if needed
        // Laravel already logs exceptions by default
    })->create();
