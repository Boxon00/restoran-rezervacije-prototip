<?php

// Ova konfiguracija omogućava da Vue frontend (Vite dev server, podrazumevano
// na http://localhost:5173) šalje zahteve ka Laravel API-ju koji radi na
// drugom portu (http://localhost:8000). Bez ovoga, browser bi blokirao
// unakrsne (cross-origin) POST/PUT/DELETE zahteve već u fazi CORS preflight
// (OPTIONS) provere, jer axios šalje prilagođeno 'Authorization' zaglavlje
// (Bearer token) i 'Content-Type: application/json', što preflight zahteva.
//
// 'supports_credentials' je namerno false: ovaj projekat koristi Bearer-token
// autentikaciju (Sanctum personal access tokens), a ne cookie-based sesiju,
// pa cookie-ji ne moraju da putuju sa zahtevom (videti napomenu u bootstrap/app.php
// o tome zašto statefulApi()/EnsureFrontendRequestsAreStateful nisu korišćeni).

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:5173'),
        'http://127.0.0.1:5173',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
