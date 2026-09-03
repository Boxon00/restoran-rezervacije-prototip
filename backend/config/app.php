<?php

return [
    'name' => env('APP_NAME', 'Rezervacije Restorana'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => 'Europe/Belgrade',
    'locale' => 'sr',
    'fallback_locale' => 'en',
    'faker_locale' => 'sr_RS',
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',
];
