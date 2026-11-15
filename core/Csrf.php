<?php
namespace SpotCore;

/**
 * Cross-Site Request Forgery protection. Every form includes a secret token
 * stored in the session; a POST without the matching token is rejected.
 */
final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function isValid(?string $token): bool
    {
        return is_string($token) && $token !== '' && !empty($_SESSION['_csrf'])
            && hash_equals($_SESSION['_csrf'], $token);
    }

    /** New token after login/logout so an old page's token stops working. */
    public static function rotate(): void
    {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}
