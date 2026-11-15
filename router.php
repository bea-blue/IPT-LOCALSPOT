<?php
/**
 * Router script for PHP's built-in development server:
 *
 *   C:\xampp\php\php.exe -S localhost:8000 -t public router.php
 *
 * Real files inside public/ (CSS, images) are served directly; every other
 * URL goes to the front controller. Nothing outside public/ is reachable.
 */
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = realpath(__DIR__ . '/public' . $path);
$publicDir = realpath(__DIR__ . '/public');

// Owner uploads are images only; never let a script inside uploads/ run.
if ($file !== false && str_starts_with($file, $publicDir . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads')
    && !preg_match('/\.(jpe?g|png|webp)$/i', $file)) {
    http_response_code(404);
    return true;
}

if ($path !== '/' && $file !== false && is_file($file) && str_starts_with($file, $publicDir)) {
    return false; // let the built-in server send the static file
}

// Browsers request this automatically; answer early so it never touches the session.
if ($path === '/favicon.ico') {
    http_response_code(204);
    return true;
}

require __DIR__ . '/public/index.php';
