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
        // Eksplicitno registrujemo CORS middleware (inače je deo Laravel-ovog
        // podrazumevanog globalnog steka kada je projekat kreiran preko
        // 'laravel new', ali pošto je ovaj bootstrap/app.php ručno pisan,
        // registrujemo ga eksplicitno da ne zavisimo od te pretpostavke).
        $middleware->prepend(\Illuminate\Http\Middleware\HandleCors::class);

        // NAPOMENA: ovaj projekat koristi čistu Bearer-token autentikaciju
        // preko Sanctum personal access tokena (Auth::user()->createToken(...)),
        // a NE cookie-based SPA autentikaciju. Zbog toga se OVDE NAMERNO ne
        // registruju statefulApi() ni EnsureFrontendRequestsAreStateful —
        // ti middleware-i uključuju CSRF proveru zasnovanu na sesiji/cookie-ju
        // za svaki zahtev sa domena iz SANCTUM_STATEFUL_DOMAINS (npr. Vite dev
        // server na localhost:5173), a naš frontend nikad ne traži CSRF cookie
        // niti ga šalje nazad — pa bi svaki POST/PUT/DELETE zahtev (registracija,
        // prijava, kreiranje rezervacije...) bio odbijen sa HTTP 419 CSRF token
        // mismatch, iako GET zahtevi rade normalno. Token-based auth uopšte ne
        // zahteva CSRF zaštitu, jer se ne oslanja na automatski poslate cookie-je.

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
