<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dodatni middleware alias ('admin') koji se, po potrebi, može koristiti
 * umesto ponavljanja provere isAdmin() unutar svakog kontrolera.
 * U trenutnoj implementaciji kontroleri i dalje eksplicitno pozivaju
 * authorizeAdmin() radi jasnoće; middleware je dostupan za rute kod kojih
 * je poznato da su isključivo administratorske (npr. buduće admin/* grupe).
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Nemate dozvolu za ovu akciju.');

        return $next($request);
    }
}
