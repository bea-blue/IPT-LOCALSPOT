<?php
namespace App\Middleware;

use SpotCore\Auth;
use SpotCore\Middleware\MiddlewareInterface;
use SpotCore\Request;
use SpotCore\Response;
use SpotCore\Session;

/** Any signed-in account (member or admin). Guests are sent to /login. */
final class MemberMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        if (!Auth::check()) {
            Session::flash('info', 'Please log in or create an account to continue.');
            return Response::redirect('/login');
        }
        return $next($request);
    }
}
