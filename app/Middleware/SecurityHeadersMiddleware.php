<?php
namespace App\Middleware;

use SpotCore\Middleware\MiddlewareInterface;
use SpotCore\Request;
use SpotCore\Response;

/** Runs AFTER the controller: adds browser security headers to every response. */
final class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        return $next($request)
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('Referrer-Policy', 'same-origin')
            ->withHeader('Content-Security-Policy', "default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; frame-ancestors 'none'; form-action 'self'");
    }
}
