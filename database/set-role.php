<?php
/**
 * CLI only: give an existing account a role. Used once to make the first
 * developer (after that, developers manage staff from the Staff page).
 *
 *   php database/set-role.php you@example.com developer
 *
 * Roles: developer | manager | employee | member. The account must already exist
 * (sign up on the website first), so this script never sees a password.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/bootstrap.php';

use App\Models\User;
use App\Support\Roles;
use SpotCore\Database;


[$script, $email, $role] = array_pad($argv, 3, null);
$allowed = array_merge(array_keys(Roles::STAFF), ['member']);
if (!$email || !in_array($role, $allowed, true)) {
    fwrite(STDERR, "Usage: php database/set-role.php <email> <" . implode('|', $allowed) . ">\n");
    exit(1);
}
$user = User::findByEmail($email);
if (!$user) {
    fwrite(STDERR, "No account uses {$email}. Sign up on the website first, then run this again.\n");
    exit(1);
}
if ($user['role'] === 'developer' && $role !== 'developer' && User::countRole('developer') <= 1) {
    fwrite(STDERR, "{$email} is the only developer. Make someone else a developer first.\n");
    exit(1);
}
if (Roles::isStaff($role) && Database::value('SELECT COUNT(*) FROM places WHERE owner_id = :id', ['id' => $user['id']]) > 0) {
    fwrite(STDERR, "{$email} owns shops. Staff accounts cannot own shops.\n");
    exit(1);
}
User::setRole((int) $user['id'], $role);
echo "{$user['name']} ({$email}) is now " . Roles::label($role) . ".\n";
