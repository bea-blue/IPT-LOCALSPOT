<?php
namespace SpotCore\Middleware;

use SpotCore\Request;
use SpotCore\Response;

/**
 * A middleware either stops the request (returns its own Response,
 * e.g. a redirect to /login) or passes it on with $next($request).
 */
interface MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response;
}
