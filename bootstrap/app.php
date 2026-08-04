<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )

    ->withMiddleware(function (Middleware $middleware): void {
        // Clever Cloud kończy SSL na swoim reverse proxy i przekazuje ruch dalej
        // zwykłym HTTP, ustawiając nagłówek X-Forwarded-Proto. Appka nie jest
        // bezpośrednio dostępna z internetu inaczej niż przez to proxy, więc
        // ufamy mu w całości, żeby Laravel poprawnie generował linki https://.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'api-key' => \App\Http\Middleware\ApiKeyMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
