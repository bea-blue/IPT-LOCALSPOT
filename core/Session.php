<?php
namespace SpotCore;

/**
 * Session wrapper with "flash" data: values that survive exactly one redirect
 * (success messages, validation errors, old form input).
 */
final class Session
{
    public static function start(bool $secure = false): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'httponly' => true,        // JavaScript can't read the cookie
                'samesite' => 'Lax',       // not sent on cross-site form posts
                'secure'   => $secure,     // only over HTTPS when the site uses HTTPS
            ]);
            session_start();
        }
        // Age flash data: last request's "new" becomes this request's "old".
        $_SESSION['_flash_old'] = $_SESSION['_flash_new'] ?? [];
        $_SESSION['_flash_new'] = [];
    }

    public static function get(string $key, mixed $default = null): mixed { return $_SESSION[$key] ?? $default; }
    public static function set(string $key, mixed $value): void { $_SESSION[$key] = $value; }
    public static function forget(string $key): void { unset($_SESSION[$key]); }

    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash_new'][$key] = $value;
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        return $_SESSION['_flash_old'][$key] ?? $default;
    }

    /** New session ID after login prevents session-fixation attacks. */
    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    /** Clears data, deletes the cookie and destroys the session (used at logout). */
    public static function destroy(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 3600, 'path' => $p['path'], 'domain' => $p['domain'],
                'secure' => $p['secure'], 'httponly' => $p['httponly'], 'samesite' => $p['samesite'] ?: 'Lax',
            ]);
            session_destroy();
        }
    }
}
