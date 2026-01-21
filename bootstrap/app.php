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
$middleware->trustProxies(at: [
    "10.42.0.0/16",
    "10.8.0.0/16",
    "10.1.0.0/16"
]);
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
