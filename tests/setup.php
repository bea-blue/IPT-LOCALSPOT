<?php
/**
 * Shared test setup for BOTH runners (SpotCore's tests/run.php and PHPUnit):
 * loads the SpotCore autoloader and an in-memory SQLite database.
 */
require_once __DIR__ . '/../core/autoload.php';

use SpotCore\Config;
use SpotCore\Database;

Config::set('app.base_path', '');
date_default_timezone_set('Asia/Manila');

if (extension_loaded('pdo_sqlite')) {
    $pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $pdo->exec('CREATE TABLE categories (id INTEGER PRIMARY KEY, name TEXT)');
    $pdo->exec("INSERT INTO categories (id, name) VALUES (1, 'Cafés'), (2, 'Parks')");
    $pdo->exec("CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT, password_hash TEXT, role TEXT NOT NULL DEFAULT 'member')");
    $pdo->exec("INSERT INTO users (id, name, email, password_hash, role) VALUES (1, 'Admin', 'admin@localspot.test', 'x', 'admin')");
    Database::setConnection($pdo);
}
