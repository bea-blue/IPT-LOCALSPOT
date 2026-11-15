<?php
namespace App\Middleware;

use App\Support\Roles;
use SpotCore\Auth;
use SpotCore\Middleware\MiddlewareInterface;
use SpotCore\Request;
use SpotCore\Response;
use SpotCore\Session;

/**
 * Protects the staff panel (/admin) on the SERVER side. Guests are redirected to
 * /login; signed-in members and owners get 403. Each staff action then checks
 * its own permission with Roles::can() (for example, an employee cannot approve).
 */
final class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        if (!Auth::check()) {
            Session::flash('info', 'Please log in to continue.');
            return Response::redirect('/login');
        }
        if (!Roles::isStaff(Auth::role())) {
            abort(403, 'Only LocalSpot staff can open this page.');
        }
        return $next($request);
    }
}
