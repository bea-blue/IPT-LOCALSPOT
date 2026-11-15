<?php
namespace SpotCore;

/** Session-based authentication. Only the user's id is kept in the session; the role is read from the database on each request. */
final class Auth
{
    private static ?array $cached = null;

    /** Same false result for "no such email" and "wrong password" (no account enumeration). */
    public static function attempt(string $email, string $password): bool
    {
        $user = Database::fetch('SELECT id, name, email, password_hash FROM users WHERE email = :email LIMIT 1', ['email' => strtolower($email)]);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }
        self::login((int) $user['id']);
        return true;
    }

    /** Starts a session for a user who was just verified or just registered. */
    public static function login(int $userId): void
    {
        Session::regenerate();
        Csrf::rotate();
        Session::set('user_id', $userId);
        self::$cached = null;
    }

    /** The signed-in user's role (what each role may do is decided by the app, not the framework). */
    public static function role(): ?string
    {
        $user = self::user();
        return $user['role'] ?? null;
    }

    public static function logout(): void
    {
        self::$cached = null;
        Session::destroy();
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        $user = self::user();
        return $user ? (int) $user['id'] : null;
    }

    public static function user(): ?array
    {
        $id = Session::get('user_id');
        if (!$id) {
            return null;
        }
        if (self::$cached === null || (int) self::$cached['id'] !== (int) $id) {
            self::$cached = Database::fetch('SELECT id, name, email, role FROM users WHERE id = :id', ['id' => $id]);
        }
        return self::$cached;
    }
}
