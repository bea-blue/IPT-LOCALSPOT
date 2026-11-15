<?php
namespace App\Middleware;

use SpotCore\Logger;
use SpotCore\Middleware\MiddlewareInterface;
use SpotCore\Request;
use SpotCore\Response;

/**
 * Logs method, path, status and duration for every request.
 * Deliberately NOT logged: query strings, form bodies, cookies, tokens, passwords.
 */
final class LoggingMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $start = microtime(true);
        $response = $next($request);
        Logger::info(sprintf('%s %s -> %d (%.1f ms)', $request->method(), $request->path(), $response->status(), (microtime(true) - $start) * 1000));
        return $response;
    }
}
