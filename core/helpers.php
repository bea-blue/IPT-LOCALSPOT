<?php
/**
 * Global helper functions used by controllers and views.
 */

use SpotCore\Config;
use SpotCore\Csrf;
use SpotCore\HttpException;
use SpotCore\Session;

/** Escape text for HTML. Use on EVERY dynamic value in a view (prevents XSS). */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Internal link that respects APP_BASE_PATH (e.g. when served from an Apache sub-folder). */
function url(string $path = '/'): string
{
    $base = rtrim((string) Config::get('app.base_path', ''), '/');
    return $base . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function csrf_field(): string
{
    return Csrf::field();
}

function old(string $key, mixed $default = ''): mixed
{
    $old = Session::getFlash('old', []);
    return array_key_exists($key, $old) ? $old[$key] : $default;
}

function error_for(string $field): ?string
{
    return Session::getFlash('errors', [])[$field] ?? null;
}

function config(string $key, mixed $default = null): mixed
{
    return Config::get($key, $default);
}

/** 150 -> "₱150", 89.5 -> "₱89.50" */
function peso(mixed $amount): string
{
    $amount = (float) $amount;
    return '₱' . number_format($amount, floor($amount) == $amount ? 0 : 2);
}

/** Throws an HttpException; the kernel renders the error page. (PHP 8.0: no `never` type.) */
function abort(int $status, string $message = ''): void
{
    throw new HttpException($status, $message);
}
