<?php
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\LoggingMiddleware;
use App\Middleware\MemberMiddleware;
use App\Middleware\SecurityHeadersMiddleware;

return [
    // Runs on every request, in this order (also wraps 404/405 pages)
    'global' => [
        LoggingMiddleware::class,
        SecurityHeadersMiddleware::class,
    ],

    // Short names used in routes/web.php
    'aliases' => [
        'staff'  => AuthMiddleware::class,   // developer, manager or employee
        'member' => MemberMiddleware::class, // any signed-in account
        'csrf' => CsrfMiddleware::class,
    ],
];
