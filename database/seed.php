<?php
/**
 * LocalSpot seeder — COMMAND LINE ONLY (refuses to run in a browser).
 *
 *   C:\xampp\php\php.exe database\seed.php
 *
 * What it does (safe to run again):
 *   1. Categories: inserts the 4 fixed categories only if missing.
 *   2. Admin: if NO user exists, asks for the admin email and a password
 *      (hashed with password_hash). If a user already exists, it is left alone.
 *   3. Places are NOT added here. Add the sourced listings with:
 *        php database\import-listings.php
 *      (The old fictional demo places were retired on Nov 15, 2026.)
 *
 * Optional non-interactive form (local machine only):
 *   php database\seed.php --email=admin@localspot.test --password=YourPassword123
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../bootstrap.php';

use SpotCore\Database;

$options = getopt('', ['email::', 'password::', 'name::']);

function line(string $text): void { fwrite(STDOUT, $text . PHP_EOL); }
function ask(string $question, string $default = ''): string
{
    fwrite(STDOUT, $question . ($default !== '' ? " [{$default}]" : '') . ': ');
    $answer = trim((string) fgets(STDIN));
    return $answer === '' ? $default : $answer;
}

try {
    Database::pdo();
} catch (PDOException $e) {
    line('Could not connect to the database. Is MySQL running in XAMPP and did you import database/schema.sql?');
    line('Details: ' . $e->getMessage());
    exit(1);
}

$now = date('Y-m-d H:i:s');

// ---------------------------------------------------------------- 1. Categories
$categories = ['Cafés', 'Study & Work Spaces', 'Parks', 'Food Spots'];
$added = 0;
foreach ($categories as $name) {
    if (!Database::value('SELECT COUNT(*) FROM categories WHERE name = :n', ['n' => $name])) {
        Database::insert('categories', ['name' => $name]);
        $added++;
    }
}
line("Categories: {$added} added, " . (count($categories) - $added) . ' already present.');

// ---------------------------------------------------------------- 2. Admin account
$adminId = Database::value("SELECT id FROM users WHERE role = 'developer' ORDER BY id LIMIT 1");
if ($adminId) {
    line('Admin: an account already exists, so it was not changed.');
} else {
    $email = strtolower((string) ($options['email'] ?? ask('Admin email', 'admin@localspot.test')));
    $name = (string) ($options['name'] ?? 'LocalSpot Admin');
    $password = (string) ($options['password'] ?? '');
    while (strlen($password) < 8) {
        $password = ask('Choose an admin password (at least 8 characters; it will be visible while typing)');
        if (strlen($password) < 8) {
            line('  Too short, try again.');
        }
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        line('That email address is not valid. Nothing was created.');
        exit(1);
    }
    $adminId = Database::insert('users', [
        'name' => $name,
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'role' => 'developer', // first staff account; promote others from Staff in the admin panel
        'created_at' => $now,
    ]);
    line("Admin: created {$email}. (The password is stored only as a hash.)");
}

// ---------------------------------------------------------------- 3. Places
line('Places: none added here. Next run:  C:\\xampp\\php\\php.exe database\\import-listings.php');
line('Then start the app with:  C:\\xampp\\php\\php.exe -S localhost:8000 -t public router.php');
