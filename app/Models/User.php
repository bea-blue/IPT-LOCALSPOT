<?php
namespace App\Models;

use App\Support\Roles;
use SpotCore\Database;
use SpotCore\Model;

/** Login itself is handled by SpotCore\Auth; this model is used for lookups and roles. */
final class User extends Model
{
    protected static string $table = 'users';
    protected static bool $hasUpdatedAt = false;

    public static function findByEmail(string $email): ?array
    {
        return Database::fetch('SELECT id, name, email, role FROM users WHERE LOWER(email) = :e LIMIT 1', ['e' => strtolower(trim($email))]);
    }

    public static function staff(): array
    {
        $roles = array_keys(Roles::STAFF);
        return Database::fetchAll(
            "SELECT id, name, email, role, created_at FROM users WHERE role IN ('" . implode("', '", $roles) . "') ORDER BY CASE role WHEN 'developer' THEN 0 WHEN 'manager' THEN 1 ELSE 2 END, name"
        );
    }

    public static function countRole(string $role): int
    {
        return (int) Database::value('SELECT COUNT(*) FROM users WHERE role = :r', ['r' => $role]);
    }

    public static function setRole(int $id, string $role): void
    {
        Database::query('UPDATE users SET role = :r WHERE id = :id', ['r' => $role, 'id' => $id]);
    }
}
