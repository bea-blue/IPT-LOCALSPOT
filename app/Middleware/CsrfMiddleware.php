<?php
namespace App\Middleware;

use SpotCore\Csrf;
use SpotCore\HttpException;
use SpotCore\Middleware\MiddlewareInterface;
use SpotCore\Request;
use SpotCore\Response;

/** Rejects a state-changing request whose _token doesn't match the session (HTTP 419). */
final class CsrfMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        if ($request->method() !== 'GET' && !Csrf::isValid($request->input('_token'))) {
            throw new HttpException(419, 'Your form expired. Go back, refresh the page and try again.');
        }
        return $next($request);
    }
}
